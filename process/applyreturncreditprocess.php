<?php
session_start();
require_once('../connection/db.php');
$userID = $_SESSION['userid'];

$returnID = $_POST['returnID'];
$applyData = json_decode($_POST['applyData'], true);

$conn->begin_transaction();

try {
    foreach($applyData as $item) {
        $invID = $item['invoiceID'];
        $amt = $item['applyAmount'];

        // Record credit application
        $sql = "INSERT INTO tbl_return_credit_apply (tbl_return_id, tbl_invoice_id, amount, user_id, date) 
                VALUES ('$returnID', '$invID', '$amt', '$userID', NOW())";
        $conn->query($sql);

        // Update invoice balance
        $conn->query("UPDATE tbl_invoice SET paymentcomplete = 1 WHERE idtbl_invoice = '$invID' AND (nettotal - COALESCE((SELECT SUM(payamount) FROM tbl_invoice_payment_has_tbl_invoice WHERE tbl_invoice_idtbl_invoice = idtbl_invoice),0)) <= $amt");
    }

    // Mark return as used for credit
    $conn->query("UPDATE tbl_return SET credit_used = 1 WHERE idtbl_return = '$returnID'");

    $conn->commit();
    echo json_encode(['status'=>1, 'message'=>'Credit applied successfully!']);
} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>0, 'message'=>'Error applying credit']);
}
?>