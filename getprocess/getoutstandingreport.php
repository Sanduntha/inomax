<?php
require_once('../connection/db.php');

$validfrom    = $_POST['validfrom']    ?? '';
$validto      = $_POST['validto']      ?? '';
$deliveryfrom = $_POST['deliveryfrom'] ?? '';
$deliveryto   = $_POST['deliveryto']   ?? '';
$customerID   = $_POST['customer']     ?? 0;
$repID        = $_POST['rep']          ?? 0;
$searchType   = $_POST['searchType']   ?? 0;
$aginvalue    = $_POST['aginvalue']    ?? 0;

$customerarray   = [];
$totalAmount     = 0;
$totalPayAmount  = 0;
$totalCreditNote = 0;
$totalBalance    = 0;

$sql = "
SELECT 
    u.nettotal,
    u.idtbl_invoice,
    u.invoiceno,
    u.total,
    u.date,
    uc.customer AS cusname,
    ue.name AS repname,
    COALESCE(pay.payamount, 0) AS payamount,
    COALESCE(pay.creditnoteamount, 0) AS creditnoteamount
FROM tbl_invoice u
LEFT JOIN tbl_customer uc 
    ON u.tbl_customer_idtbl_customer = uc.idtbl_customer
LEFT JOIN tbl_customer_order ud 
    ON u.tbl_customer_order_idtbl_customer_order = ud.idtbl_customer_order
LEFT JOIN tbl_employee ue 
    ON ud.tbl_employee_idtbl_employee = ue.idtbl_employee
LEFT JOIN (
    SELECT 
        uph.tbl_invoice_idtbl_invoice AS invoice_id,
        SUM(
            CASE WHEN ipd.method IN (1, 2) 
            THEN ipd.amount * (uph.payamount / pay_totals.total_payamount)
            ELSE 0 END
        ) AS payamount,
        SUM(
            CASE WHEN ipd.method = 3 
            THEN ipd.amount * (uph.payamount / pay_totals.total_payamount)
            ELSE 0 END
        ) AS creditnoteamount
    FROM tbl_invoice_payment_has_tbl_invoice uph
    INNER JOIN tbl_invoice_payment ip
        ON uph.tbl_invoice_payment_idtbl_invoice_payment = ip.idtbl_invoice_payment
    INNER JOIN tbl_invoice_payment_detail ipd
        ON ip.idtbl_invoice_payment = ipd.tbl_invoice_payment_idtbl_invoice_payment
    INNER JOIN (
        SELECT 
            tbl_invoice_payment_idtbl_invoice_payment,
            SUM(payamount) AS total_payamount
        FROM tbl_invoice_payment_has_tbl_invoice
        GROUP BY tbl_invoice_payment_idtbl_invoice_payment
    ) pay_totals 
        ON uph.tbl_invoice_payment_idtbl_invoice_payment = pay_totals.tbl_invoice_payment_idtbl_invoice_payment
    WHERE pay_totals.total_payamount > 0
    GROUP BY uph.tbl_invoice_idtbl_invoice
) pay ON u.idtbl_invoice = pay.invoice_id
WHERE u.status = 1
AND u.paymentcomplete = 0
";

if (!empty($validfrom) && !empty($validto)) {
    $sql .= " AND u.date BETWEEN '$validfrom' AND '$validto'";
}

if ($searchType == '3' && $customerID > 0) {
    $sql .= " AND u.tbl_customer_idtbl_customer = '$customerID'";
}

if ($searchType == '2' && $repID > 0) {
    $sql .= " AND ue.idtbl_employee = '$repID'";
}

if (!empty($deliveryfrom) && !empty($deliveryto)) {
    $sql .= " 
        AND ud.delivereddatetime IS NOT NULL
        AND ud.delivereddatetime BETWEEN 
            '$deliveryfrom 00:00:00' 
            AND '$deliveryto 23:59:59'
    ";
}

$sql .= " ORDER BY uc.customer ASC";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div style='color:red;font-size:20px;'>No Records</div>";
    return;
}

while ($row = $result->fetch_assoc()) {

    $invoiceDate = new DateTime($row['date']);
    $today       = new DateTime();
    $datecount   = $today->diff($invoiceDate)->days;

    if (
        $aginvalue == 0 ||
        ($aginvalue == 1 && $datecount <= 15) ||
        ($aginvalue == 2 && $datecount > 15 && $datecount <= 30) ||
        ($aginvalue == 3 && $datecount > 30 && $datecount <= 45)
    ) {
        $customerarray[] = $row;

        $totalAmount     += $row['nettotal'];
        $totalPayAmount  += $row['payamount'];
        $totalCreditNote += $row['creditnoteamount'];
        $totalBalance    += ($row['nettotal'] - $row['payamount'] - $row['creditnoteamount']);
    }
}

if (empty($customerarray)) {
    echo "<div style='color:red;font-size:20px;'>No Records</div>";
    return;
}

$html = '
<table class="table table-striped table-bordered table-sm small" id="outstandingReportTable">
<thead>
<tr>
    <th>Customer</th>
    <th class="text-center">Rep</th>
    <th class="text-center">Date</th>
    <th class="text-center">Days</th>
    <th class="text-center">Invoice</th>
    <th class="text-center">Invoice Total</th>
    <th class="text-center">Pay Amount<br><small class="text-muted">(Cash/Cheque)</small></th>
    <th class="text-center">Credit Note</th>
    <th class="text-center">Balance</th>
</tr>
</thead>
<tbody>
';

foreach ($customerarray as $row) {

    $invoiceDate = new DateTime($row['date']);
    $today       = new DateTime();
    $datecount   = $today->diff($invoiceDate)->days;

    $netTotal   = $row['nettotal']         ?? 0;
    $payAmount  = $row['payamount']        ?? 0;
    $creditNote = $row['creditnoteamount'] ?? 0;
    $balance    = $netTotal - $payAmount - $creditNote;

    $rowClass = '';
    // if ($datecount > 30) {
    //     $rowClass = 'class="table-danger"';
    // } elseif ($datecount > 15) {
    //     $rowClass = 'class="table-warning"';
    // }

    $html .= '
    <tr ' . $rowClass . '>
        <td>' . htmlspecialchars($row['cusname']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['repname']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['date']) . '</td>
        <td class="text-center">' . $datecount . '</td>
        <td class="text-center">' . htmlspecialchars($row['invoiceno']) . '</td>
        <td class="text-right">' . number_format($netTotal, 2) . '</td>
        <td class="text-right">' . number_format($payAmount, 2) . '</td>
        <td class="text-right">' . number_format($creditNote, 2) . '</td>
        <td class="text-right">' . number_format($balance, 2) . '</td>
    </tr>';
}

$html .= '
</tbody>
<tfoot>
<tr class="font-weight-bold bg-light">
    <td colspan="5" class="text-right"><strong>Total</strong></td>
    <td class="text-right"><strong>' . number_format($totalAmount, 2) . '</strong></td>
    <td class="text-right"><strong>' . number_format($totalPayAmount, 2) . '</strong></td>
    <td class="text-right"><strong>' . number_format($totalCreditNote, 2) . '</strong></td>
    <td class="text-right"><strong>' . number_format($totalBalance, 2) . '</strong></td>
</tr>
</tfoot>
</table>';

echo $html;
?>