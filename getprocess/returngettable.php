<?php
require_once('../connection/db.php');
session_start();
$invoiceId = $_POST['invoiceId'];

// Get default tax rate
$taxQuery = "SELECT `rate` FROM `tbl_tax` LIMIT 1";
$taxResult = $conn->query($taxQuery);
$actualvat = 18;
if ($taxResult && $taxResult->num_rows > 0) {
    $taxRow = $taxResult->fetch_assoc();
    $actualvat = $taxRow['rate'];
}

// Get invoice-level discount, total, and customer VAT status
$sqlorder = "SELECT `i`.`discount` AS `invoice_discount`, `i`.`total` AS `invoice_total`, `c`.`vat_num`
             FROM `tbl_invoice` AS `i`
             LEFT JOIN `tbl_customer` AS `c` ON (`c`.`idtbl_customer` = `i`.`tbl_customer_idtbl_customer`)
             WHERE `i`.`idtbl_invoice` = '$invoiceId'";
$resultorder = $conn->query($sqlorder);
$invoice_discount = 0;
$invoice_total = 0;
$vat_num = '';
if ($resultorder && $resultorder->num_rows > 0) {
    $roworder = $resultorder->fetch_assoc();
    $vat_num = isset($roworder['vat_num']) ? trim($roworder['vat_num']) : '';
    $invoice_discount = floatval($roworder['invoice_discount']);
    $invoice_total    = floatval($roworder['invoice_total']);
}
$isVatCustomer = !empty($vat_num);

// Get all line items with their per-line discounts
$sql = "SELECT `d`.`tbl_product_idtbl_product`, `d`.`idtbl_invoice_detail`, 
               `p`.`product_name`, `d`.`saleprice`, `d`.`qty`, `d`.`discount`
        FROM `tbl_invoice` AS `i`
        LEFT JOIN `tbl_invoice_detail` AS `d` ON (`i`.`idtbl_invoice` = `d`.`tbl_invoice_idtbl_invoice`)
        LEFT JOIN `tbl_product` AS `p` ON (`p`.`idtbl_product` = `d`.`tbl_product_idtbl_product`)
        WHERE `i`.`idtbl_invoice` = '$invoiceId'";
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Sum all line-level discounts from tbl_invoice_detail
$total_line_discount = 0;
foreach ($rows as $row) {
    $total_line_discount += floatval($row['discount']);
}

// PO discount = invoice total discount minus sum of line discounts
// Then express as % of (invoice_total - total_line_discount) for use in calculateTotals()
$po_discount_flat = $invoice_discount - $total_line_discount;
$base_after_line_discounts = $invoice_total - $total_line_discount;
$podiscountpercentage = 0;
if ($base_after_line_discounts > 0 && $po_discount_flat > 0) {
    $podiscountpercentage = ($po_discount_flat / $base_after_line_discounts) * 100;
}

$array = [];
foreach ($rows as $row) {
    $saleprice  = floatval($row['saleprice']);
    $qty        = intval($row['qty']);
    $lineDiscount = floatval($row['discount']); // total line discount for this product (all qty)

    if ($isVatCustomer) {
        $base_price           = $saleprice / (1 + ($actualvat / 100));
        $base_discount_total  = $lineDiscount / (1 + ($actualvat / 100));
    } else {
        $base_price           = $saleprice;
        $base_discount_total  = $lineDiscount;
    }
    $base_discount_per_unit = ($qty > 0) ? ($base_discount_total / $qty) : 0;

    $array[] = [
        "productid"           => $row['tbl_product_idtbl_product'],
        "invoicedetailid"     => $row['idtbl_invoice_detail'],
        "productname"         => $row['product_name'],
        "saleprice"           => $saleprice,
        "qty"                 => $qty,
        "isVatCustomer"       => $isVatCustomer ? 1 : 0,
        "vatrate"             => $actualvat,
        "podiscountpercentage" => round($podiscountpercentage, 6),
        "baseprice"           => round($base_price, 4),
        "basediscountperunit" => round($base_discount_per_unit, 4)
    ];
}
print(json_encode($array));
