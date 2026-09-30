<?php
session_start();
require_once('../connection/db.php');
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

$today = date('Y-m-d');
$today2 = date('Y/m');
$last_two_digits = substr($today2, 2);
$recordID = $_GET['id'];

$empty = 'null';
$fulltot = 0;
$discount = 0;
$totaloutstanding = 0;
$fulloutstanding = 0;
$totalpayment = 0;
$net_total = 0;
$newtemp = 0;

$sqlinvoiceinfo = "SELECT `tbl_invoice`.`discount`, `tbl_invoice`.`idtbl_invoice`, `tbl_invoice`.`invoiceno`, `tbl_invoice`.`date`, `tbl_invoice`.`total`, `tbl_invoice`.`paymentcomplete`, `tbl_locations`.`idtbl_locations`, `tbl_locations`.`locationname`, `tbl_customer`.`customer`, `tbl_customer`.`address`, `tbl_customer`.`phone`, `tbl_customer`.`vat_num`, `tbl_employee`.`name` AS `saleref`, `tbl_employee`.`phone` AS 'salesrepphone', `tbl_area`.`area`, `tbl_user`.`name` as `username`, `tbl_invoice`.`tbl_customer_idtbl_customer`, `tbl_customer_order`.`cuspono` FROM `tbl_invoice` LEFT JOIN `tbl_locations` ON `tbl_locations`.`idtbl_locations`=`tbl_invoice`.`tbl_locations_idtbl_locations` LEFT JOIN `tbl_customer` ON `tbl_customer`.`idtbl_customer`=`tbl_invoice`.`tbl_customer_idtbl_customer` LEFT JOIN `tbl_customer_order` ON `tbl_customer_order`.`idtbl_customer_order`=`tbl_invoice`.`tbl_customer_order_idtbl_customer_order` LEFT JOIN `tbl_employee` ON `tbl_employee`.`idtbl_employee`=`tbl_customer_order`.`tbl_employee_idtbl_employee` LEFT JOIN `tbl_area` ON `tbl_area`.`idtbl_area`=`tbl_invoice`.`tbl_area_idtbl_area` LEFT JOIN `tbl_user` ON `tbl_user`.`idtbl_user`=`tbl_invoice`.`tbl_user_idtbl_user` WHERE `tbl_invoice`.`status`=1 AND `tbl_invoice`.`idtbl_invoice`='$recordID'";

$resultinvoiceinfo = $conn->query($sqlinvoiceinfo);

if (!$resultinvoiceinfo) {
    die("Database Error: " . $conn->error . "<br>Query: " . $sqlinvoiceinfo);
}

if ($resultinvoiceinfo->num_rows == 0) {
    die("No invoice found with ID: " . $recordID);
}

$rowinvoiceinfo = $resultinvoiceinfo->fetch_assoc();

$customerID = $rowinvoiceinfo['tbl_customer_idtbl_customer'];
$customerPhone = $rowinvoiceinfo['phone'];
$customername = $rowinvoiceinfo['customer'];
$location = $rowinvoiceinfo['locationname'];
$customeraddress = $rowinvoiceinfo['address'];
$paymentcomplete = $rowinvoiceinfo['paymentcomplete'];
$invoID = $rowinvoiceinfo['idtbl_invoice'];
$invoiceno = $rowinvoiceinfo['invoiceno']; 
$pono = $rowinvoiceinfo['cuspono']; 
$salesrepphone = $rowinvoiceinfo['salesrepphone']; 
$invoiceDate = $rowinvoiceinfo['date']; 
$empname = $rowinvoiceinfo['saleref']; 
$empphone = $rowinvoiceinfo['salesrepphone'];
$vat_num = isset($rowinvoiceinfo['vat_num']) ? trim($rowinvoiceinfo['vat_num']) : '';

$isTaxCustomer = !empty($vat_num);

$sqlinvoicedetail = "SELECT `tbl_product`.`product_name`, `tbl_product`.`product_code`, `tbl_product`.`idtbl_product`, `tbl_invoice_detail`.`qty`, `tbl_invoice_detail`.`saleprice`, `tbl_invoice_detail`.`discount` FROM `tbl_invoice_detail` LEFT JOIN `tbl_product` ON `tbl_product`.`idtbl_product`=`tbl_invoice_detail`.`tbl_product_idtbl_product` WHERE `tbl_invoice_detail`.`tbl_invoice_idtbl_invoice`='$recordID' AND `tbl_invoice_detail`.`status`=1";
$resultinvoicedetail = $conn->query($sqlinvoicedetail);

if (!$resultinvoicedetail) {
    die("Database Error: " . $conn->error . "<br>Query: " . $sqlinvoicedetail);
}

// Get payment info for outstanding balance
$sqlpayments = "SELECT SUM(amount) as total_paid FROM tbl_payment WHERE tbl_invoice_idtbl_invoice='$recordID' AND status=1";
$resultpayments = $conn->query($sqlpayments);

if ($resultpayments) {
    $rowpayments = $resultpayments->fetch_assoc();
    $totalpayment = $rowpayments['total_paid'] ?? 0;
} else {
    $totalpayment = 0;
}

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ENOMAX Holdings (PVT) LTD - Invoice</title>
<style>
    * { 
        font-size: 9px; 
        margin: 0; 
        padding: 0;
        font-family: Arial, sans-serif;
    }
    body {
        margin: 0;
        padding: 0.3cm;
    }
    .header-box {
        background-color: #005EB8;
        padding: 0.3cm;
        margin-bottom: 0.2cm;
        border: 2px solid #000;
    }
    .company-name {
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 0.1cm;
        color: #FFFFFF;
    }
    .company-info {
        font-size: 8px;
        text-align: center;
        color: #FFFFFF;
    }
    .customer-section {
        border: 1px solid #000;
        padding: 0.3cm;
        min-height: 3cm;
        float: left;
        width: 55%;
        margin-bottom: 0.2cm;
        line-height: 1.6; 
    }
    .invoice-details {
        float: right;
        width: 40%;
        margin-bottom: 0.2cm;
    }
    .tax-invoice-label {
        background-color: #005EB8;
        padding: 0.2cm;
        text-align: center;
        font-weight: bold;
        font-size: 11px;
        margin-bottom: 0.1cm;
        color: #FFFFFF;
    }
    .detail-row {
        border: 1px solid #000;
        padding: 0.1cm 0.2cm;
        margin-bottom: 1px;
        min-height: 0.5cm;
        display: flex;
        align-items: center;
    }
    .detail-label {
        font-weight: bold;
        width: 45%;
    }
    table.items {
        width: 100%;
        border-collapse: collapse;
        clear: both;
        margin-top: 0.2cm;
    }
    table.items th {
        background-color: #005EB8;
        color: #FFFFFF;
        font-weight: bold;
        padding: 0.15cm;
        border: 1px solid #000;
        font-size: 9px;
    }
    table.items td {
        border: 1px solid #000;
        padding: 0.1cm;
        font-size: 8.5px;
    }
    .totals-section {
        float: right;
        width: 40%;
        margin-top: 0.2cm;
    }
    .totals-row {
        border: 1px solid #000;
        padding: 0.1cm 0.2cm;
        margin-bottom: 1px;
        text-align: right;
    }
    .footer-notes {
        clear: both;
        margin-top: 0.3cm;
        font-size: 7px;
        line-height: 1.3;
    }
    .footer-notes div {
        margin-bottom: 0.1cm;
    }
    .signature-section {
        margin-top: 0.5cm;
        border-top: 2px solid #005EB8;
        padding-top: 0.3cm;
        clear: both;
        padding-left: 0.5cm;
        padding-right: 0.5cm;
    }
    .signature-box {
        width: 31%;
        float: left;
        text-align: center;
        padding: 0.2cm;
        margin: 0 1%;
    }
    .signature-box:first-of-type {
        margin-left: 0;
    }
    .signature-box:last-of-type {
        margin-right: 0;
        padding-right: 0;
    }
    .signature-line {
        border-bottom: 1px solid #333;
        height: 1.2cm;
        margin-bottom: 0.2cm;
    }
    .signature-label {
        font-weight: bold;
        font-size: 9px;
        color: #005EB8;
    }
    .signature-sublabel {
        font-size: 7px;
        color: #666;
        margin-top: 0.05cm;
    }
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 72px;
        color: rgba(0, 166, 81, 0.1);
        font-weight: bold;
        z-index: -1;
    }
</style>
</head>
<body>
<div class="watermark">ENOMAX</div>
<div class="header-box">
    <div class="company-name">ENOMAX HOLDINGS (PVT) LTD</div>
    <div class="company-info">
        No.46, Garden City, Minuwangoda Road, Ja-ela.<br>
        Tel: 011 3468568
    </div>
</div>

<div class="customer-section">
    <strong>Customer ID:</strong> ' . htmlspecialchars($customerID) . '<br>
    <strong style="font-size: 11px;">' . htmlspecialchars($customername) . '</strong><br>
    ' . htmlspecialchars($customeraddress) . '<br>
    <strong>Tel:</strong> ' . htmlspecialchars($customerPhone) . '
</div>

<div class="invoice-details">
';
if ($isTaxCustomer) {
    $html .= '<div class="tax-invoice-label">TAX INVOICE</div>';
} else {
    $html .= '<div class="tax-invoice-label">INVOICE</div>';
}
$html .= '
  <table style="width:100%; border-collapse:collapse; margin-top:0.2cm;">
    <tr>
        <td style="font-weight:bold; width:4.5cm; padding-bottom:6px;">Date :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($invoiceDate) . '</td>
    </tr>
    <tr>
        <td style="font-weight:bold; padding-bottom:6px;">Invoice No :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($invoiceno) . '</td>
    </tr>
    <tr>
        <td style="font-weight:bold; padding-bottom:6px;">Purchase Order No :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($pono) . '</td>
    </tr>
    <tr>
        <td style="font-weight:bold; padding-bottom:6px;">Store Location :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($location) . '</td>
    </tr>
    <tr>
        <td style="font-weight:bold; padding-bottom:6px;">Sales Executive :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($empname) . '</td>
    </tr>
    <tr>
        <td style="font-weight:bold; padding-bottom:6px;">Contact :</td>
        <td style="padding-bottom:6px;">' . htmlspecialchars($empphone) . '</td>
    </tr>
</table>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width: 10%;">CODE</th>
            <th style="width: 42%;">DESCRIPTION</th>
            <th style="width: 8%;">QTY</th>
            <th style="width: 13%;">UNIT PRICE</th>
            <th style="width: 10%;">DIS.</th>
            <th style="width: 17%;">AMOUNT</th>
        </tr>
    </thead>
    <tbody>';

$count = 0;
while ($rowinvoicedetail = $resultinvoicedetail->fetch_assoc()) {
    $count++;
    $qty = (float)$rowinvoicedetail['qty'];
    $saleprice = (float)$rowinvoicedetail['saleprice'];
    $linediscount = (float)$rowinvoicedetail['discount'];

    if ($isTaxCustomer) {
        // For tax customers, remove 18% VAT from the sale price to get the base price
        $unit_ex_vat = $saleprice / 1.18;
        $line_total_ex_vat = ($unit_ex_vat * $qty) - $linediscount;
        $fulltot += $line_total_ex_vat;
        $display_unit_price = number_format($unit_ex_vat, 2);
        $display_line_amount = number_format($line_total_ex_vat, 2);
    } else {
        // For non-tax customers, use the price as-is
        $unit_ex_vat = $saleprice;
        $line_total_ex_vat = ($unit_ex_vat * $qty) - $linediscount;
        $fulltot += $line_total_ex_vat;
        $display_unit_price = number_format($unit_ex_vat, 2);
        $display_line_amount = number_format($line_total_ex_vat, 2);
    }

    $html .= '
        <tr>
            <td>' . $count . ' ' . htmlspecialchars($rowinvoicedetail['product_code']) . '</td>
            <td>' . htmlspecialchars($rowinvoicedetail['product_name']) . '</td>
            <td align="center">' . $qty . '</td>
            <td align="right">' . $display_unit_price . '</td>
            <td align="right">' . number_format($linediscount, 2) . '</td>
            <td align="right">' . $display_line_amount . '</td>
        </tr>';
}

$html .= '
    </tbody>
</table>

<div class="totals-section">
    <div class="totals-row">Sub Total: <strong>' . number_format($fulltot, 2) . '</strong></div>
    <div class="totals-row">Discount: <strong>' . number_format($rowinvoiceinfo['discount'], 2) . '</strong></div>
    <div class="totals-row">Total With Discount: <strong>' . number_format($fulltot - $rowinvoiceinfo['discount'], 2) . '</strong></div>';

$discount = (float)$rowinvoiceinfo["discount"];
$net_total_before_vat = $fulltot - $discount;
$vat_amount = 0;
$grand_total = $net_total_before_vat;

if ($isTaxCustomer) {
    // Add 18% VAT to the total for tax customers
    $vat_amount = $net_total_before_vat * 0.18;
    $grand_total = $net_total_before_vat + $vat_amount;
    $html .= '
    <div class="totals-row">VAT (18%): <strong>' . number_format($vat_amount, 2) . '</strong></div>
    <div class="totals-row" style="background-color: #E6F4EA;">Net Total With VAT: <strong>' . number_format($grand_total, 2) . '</strong></div>';
} else {
    $html .= '
    <div class="totals-row" style="background-color: #E6F4EA;">Net Total: <strong>' . number_format($grand_total, 2) . '</strong></div>';
}

$html .= '
</div>

<div class="footer-notes">
    <div>* Payment must be completed within 45 days</div>
    <div>* Received the above goods in good condition</div>
</div>

<div class="signature-section">
    <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Prepared By</div>
        <div class="signature-sublabel">ENOMAX HOLDINGS (PVT) LTD</div>
    </div>
    <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Authorized By</div>
    </div>
    <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Customer Signature</div>
        <div class="signature-sublabel">Received By</div>
    </div>
    <div style="clear: both;"></div>
</div>

</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Invoice_" . $invoiceno . ".pdf", ["Attachment" => 0]);
exit;