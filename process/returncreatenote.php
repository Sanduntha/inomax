<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location:../index.php");
}
require_once('../connection/db.php');
$userID = $_SESSION['userid'];

$returnId = isset($_POST['returnId']) ? intval($_POST['returnId']) : 0;
$customerId = isset($_POST['customerId']) ? intval($_POST['customerId']) : 0;
$creditNoteId = isset($_POST['creditNoteId']) ? intval($_POST['creditNoteId']) : 0;
$allocations = isset($_POST['allocations']) ? json_decode($_POST['allocations'], true) : array();
$creditTotal = isset($_POST['creditTotal']) ? floatval($_POST['creditTotal']) : 0;

$updatedatetime = date('Y-m-d H:i:s');
$today = date('Y-m-d');

$totalApplied = 0;
foreach ($allocations as $a) {
    $amt = floatval($a['amount']);
    if ($amt > 0) {
        $totalApplied += $amt;
    }
}

if ($creditNoteId <= 0) {
    $returnSql = "SELECT `total`, `tbl_customer_idtbl_customer`, `returntype`, `acceptance_status` FROM `tbl_return` WHERE `idtbl_return` = '$returnId' LIMIT 1";
    $returnResult = $conn->query($returnSql);
    if (!$returnResult || $returnResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Return record not found.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Return record not found.', 'type' => 'danger'])]);
        exit;
    }

    $returnRow = $returnResult->fetch_assoc();
    if ($returnRow['returntype'] != 1 || $returnRow['acceptance_status'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Return is not an accepted customer return.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Return is not an accepted customer return.', 'type' => 'danger'])]);
        exit;
    }

    $creditAmount = floatval($returnRow['total']);
    $customerId = intval($returnRow['tbl_customer_idtbl_customer']);
    $insertCredit = "INSERT INTO `tbl_creditenote`(`returnamount`, `payAmount`, `balAmount`, `baltotalamount`, `settle`, `status`, `updatedatetime`, `tbl_user_idtbl_user`, `tbl_customer_idtbl_customer`) VALUES ('$creditAmount', 0, '$creditAmount', 0, 0, 1, '$updatedatetime', '$userID', '$customerId')";
    if ($conn->query($insertCredit) !== TRUE) {
        echo json_encode(['success' => false, 'message' => 'Unable to create credit note.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Unable to create credit note.', 'type' => 'danger'])]);
        exit;
    }

    $creditNoteId = $conn->insert_id;
    $insertCreditDetail = "INSERT INTO `tbl_creditenote_detail`(`returntotal`, `status`, `updatedatetime`, `tbl_user_idtbl_user`, `tbl_return_idtbl_return`, `tbl_creditenote_idtbl_creditenote`) VALUES ('$creditAmount', 1, '$updatedatetime', '$userID', '$returnId', '$creditNoteId')";
    $conn->query($insertCreditDetail);

    $currentPayAmount = 0;
    $currentBalAmount = $creditAmount;
} else {
    $creditNoteSql = "SELECT `payAmount`, `balAmount`, `settle` FROM `tbl_creditenote` WHERE `idtbl_creditenote` = '$creditNoteId' LIMIT 1";
    $creditNoteResult = $conn->query($creditNoteSql);
    if (!$creditNoteResult || $creditNoteResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Credit note not available.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Credit note not available.', 'type' => 'danger'])]);
        exit;
    }
    $creditNoteRow = $creditNoteResult->fetch_assoc();
    $currentPayAmount = floatval($creditNoteRow['payAmount']);
    $currentBalAmount = floatval($creditNoteRow['balAmount']);
    if (intval($creditNoteRow['settle']) === 1 || $currentBalAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Credit note has already been settled or has no available balance.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Credit note has already been settled or has no available balance.', 'type' => 'danger'])]);
        exit;
    }
}

if ($totalApplied > $currentBalAmount) {
    echo json_encode(['success' => false, 'message' => 'Allocated amount exceeds available credit.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Allocated amount exceeds available credit.', 'type' => 'danger'])]);
    exit;
}

$paymentID = 0;
if ($totalApplied > 0) {
    $insertpayment = "INSERT INTO `tbl_invoice_payment`(`date`, `payment`, `balance`, `status`, `updatedatetime`, `tbl_user_idtbl_user`) VALUES ('$today','$totalApplied','0','1','$updatedatetime','$userID')";
    if ($conn->query($insertpayment) !== TRUE) {
        echo json_encode(['success' => false, 'message' => 'Unable to create payment record.', 'action' => json_encode(['icon' => 'fas fa-exclamation-triangle', 'title' => 'Error', 'message' => 'Unable to create payment record.', 'type' => 'danger'])]);
        exit;
    }
    $paymentID = $conn->insert_id;

    foreach ($allocations as $a) {
        $invoiceID = intval($a['invoiceID']);
        $amt = floatval($a['amount']);
        if ($amt <= 0) continue;

        $sqlinv = "SELECT IFNULL(total,0) AS total FROM tbl_invoice WHERE idtbl_invoice = '$invoiceID' LIMIT 1";
        $resinv = $conn->query($sqlinv);
        $rowinv = $resinv ? $resinv->fetch_assoc() : ['total' => 0];
        $invamount = floatval($rowinv['total']);

        $insertHas = "INSERT INTO `tbl_invoice_payment_has_tbl_invoice`(`tbl_invoice_payment_idtbl_invoice_payment`, `tbl_invoice_idtbl_invoice`, `total`, `discount`, `payamount`, `fullstatus`, `halfstatus`) VALUES ('$paymentID','$invoiceID','0','0','$amt','0','0')";
        $conn->query($insertHas);

        $sqlsumpayment = "SELECT SUM(`payamount`) AS `netpayment` FROM `tbl_invoice_payment_has_tbl_invoice` WHERE `tbl_invoice_idtbl_invoice`='$invoiceID'";
        $resultsumpayment = $conn->query($sqlsumpayment);
        $rowsumpayment = $resultsumpayment->fetch_assoc();
        $netpayment = floatval($rowsumpayment['netpayment']);

        if ($invamount <= $netpayment) {
            $fullstatus = 1;
            $halfstatus = 0;
        } else {
            $fullstatus = 0;
            $halfstatus = 1;
        }

        $updateHasStatus = "UPDATE `tbl_invoice_payment_has_tbl_invoice` SET `fullstatus`='$fullstatus', `halfstatus`='$halfstatus' WHERE `tbl_invoice_payment_idtbl_invoice_payment`='$paymentID' AND `tbl_invoice_idtbl_invoice`='$invoiceID'";
        $conn->query($updateHasStatus);

        $paymentcompletestatus = ($invamount <= $netpayment) ? 1 : 0;
        $updateinvoice = "UPDATE `tbl_invoice` SET `paymentcomplete`='$paymentcompletestatus',`updatedatetime`='$updatedatetime',`tbl_user_idtbl_user`='$userID' WHERE `idtbl_invoice`='$invoiceID'";
        $conn->query($updateinvoice);
    }

    $insertpaydetail = "INSERT INTO `tbl_invoice_payment_detail`(`method`, `amount`, `branch`, `receiptno`, `chequeno`, `chequedate`, `status`, `creditnoteid`, `updatedatetime`, `tbl_bank_idtbl_bank`, `tbl_user_idtbl_user`, `tbl_invoice_payment_idtbl_invoice_payment`) VALUES ('3','$totalApplied','-','','','','$creditNoteId','1','$updatedatetime','0','$userID','$paymentID')";
    $conn->query($insertpaydetail);
}

$newPayAmount = $currentPayAmount + $totalApplied;
$newBalAmount = $currentBalAmount - $totalApplied;
$settleStatus = ($newBalAmount <= 0) ? 1 : 0;
$settledateSql = ($settleStatus === 1) ? ", settledate='$today'" : '';
$updateCredit = "UPDATE `tbl_creditenote` SET `payAmount`='$newPayAmount', `balAmount`='$newBalAmount', `settle`='$settleStatus' $settledateSql WHERE `idtbl_creditenote`='$creditNoteId'";
$conn->query($updateCredit);

$actionObj = new stdClass();
$actionObj->icon = 'fas fa-check-circle';
$actionObj->title = 'Credit Applied';
$actionObj->message = $totalApplied > 0 ? 'Credit applied to invoices successfully.' : 'No invoice allocations entered. Credit note remains available.';
$actionObj->url = '';
$actionObj->target = '_blank';
$actionObj->type = 'success';

$obj = new stdClass();
$obj->success = true;
$obj->paymentinvoice = $paymentID;
$obj->message = $actionObj->message;
$obj->action = $actionObj;

echo json_encode($obj);
