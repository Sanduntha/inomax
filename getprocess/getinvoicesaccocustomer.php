<?php
require_once('../connection/db.php');
$customerId = $_POST['customerId'];

// Check VAT status of customer
$sqlvat = "SELECT `vat_num` FROM `tbl_customer` WHERE `idtbl_customer` = '$customerId'";
$resultvat = $conn->query($sqlvat);
$vat_num = '';
if ($resultvat && $resultvat->num_rows > 0) {
    $rowvat = $resultvat->fetch_assoc();
    $vat_num = isset($rowvat['vat_num']) ? trim($rowvat['vat_num']) : '';
}
$isVatCustomer = !empty($vat_num) ? 1 : 0;

$sql = "SELECT `idtbl_invoice`, `invoiceno` FROM `tbl_invoice` WHERE `status`=1 AND `tbl_customer_idtbl_customer` = '$customerId'";
$result = $conn->query($sql);
$arraylist = array();
while ($row = $result->fetch_assoc()) {
    $obj = new stdClass();
    $obj->invoiceId = $row['idtbl_invoice'];
    $obj->invoiceNo = $row['invoiceno'];
    $obj->isVatCustomer = $isVatCustomer;

    array_push($arraylist, $obj);
}
echo json_encode($arraylist);
