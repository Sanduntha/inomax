<?php
session_start();
require_once('../connection/db.php');

$customerID = isset($_POST['customerID']) ? intval($_POST['customerID']) : 0;

if ($customerID <= 0) {
    echo '<div class="alert alert-warning">No customer selected.</div>';
    exit;
}

$sql = "SELECT i.idtbl_invoice, IFNULL(i.invoiceno, i.idtbl_invoice) AS invoiceno, i.date AS invoicedate, IFNULL(i.total,0) AS total, (IFNULL(i.total,0) - IFNULL(p.netpayment,0)) AS outstanding FROM `tbl_invoice` i LEFT JOIN (SELECT `tbl_invoice_idtbl_invoice`, SUM(`payamount`) AS netpayment FROM `tbl_invoice_payment_has_tbl_invoice` GROUP BY `tbl_invoice_idtbl_invoice`) p ON p.`tbl_invoice_idtbl_invoice` = i.`idtbl_invoice` WHERE i.`tbl_customer_idtbl_customer` = '$customerID' AND i.`status` = 1 AND i.`paymentcomplete` = 0 AND (IFNULL(i.`total`,0) - IFNULL(p.`netpayment`,0)) > 0";

$result = $conn->query($sql);

if (!$result) {
    echo '<div class="alert alert-danger">Error loading invoices: ' . htmlspecialchars($conn->error) . '</div>';
    exit;
}

if ($result->num_rows == 0) {
    echo '<div class="alert alert-info">No pending invoices for this customer.</div>';
    exit;
}

$html = '<table class="table table-sm table-bordered">';
$html .= '<thead><tr><th>Invoice</th><th>Date</th><th class="text-right">Outstanding</th><th class="text-right">Allocate</th></tr></thead><tbody>';

while ($r = $result->fetch_assoc()) {
    $out = number_format($r['outstanding'], 2);
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($r['invoiceno']) . '</td>';
    $html .= '<td>' . htmlspecialchars($r['invoicedate']) . '</td>';
    $html .= '<td class="text-right">Rs.' . $out . '</td>';
    $html .= '<td class="text-right"><input type="number" min="0" step="0.01" class="form-control form-control-sm allocAmt" data-invoiceid="' . $r['idtbl_invoice'] . '" value="0"></td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

echo $html;
