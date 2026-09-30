<?php
session_start();
require_once('../connection/db.php');
require_once '../vendor/autoload.php';

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);

use Dompdf\Dompdf;
use Dompdf\Options;

// ==================== NUMBER TO WORDS FUNCTION ====================
function ConvertRupeeToText($amount)
{
    $ones = array(
        0 => '',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen'
    );
    $tens = array(2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty', 6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety');

    $amount = str_replace(',', '', $amount);
    $rupees = intval($amount);
    $cents = intval(round(($amount - $rupees) * 100));

    $numberToWords = function ($num) use (&$numberToWords, $ones, $tens) {
        $str = '';
        if ($num >= 1000000000) {
            $str .= $numberToWords(intval($num / 1000000000)) . ' billion ';
            $num %= 1000000000;
        }
        if ($num >= 1000000) {
            $str .= $numberToWords(intval($num / 1000000)) . ' million ';
            $num %= 1000000;
        }
        if ($num >= 1000) {
            $str .= $numberToWords(intval($num / 1000)) . ' thousand ';
            $num %= 1000;
        }
        if ($num >= 100) {
            $str .= $ones[intval($num / 100)] . ' hundred ';
            $num %= 100;
        }
        if ($num > 0) {
            if ($str !== '') {
                $str .= ' ';
            }
            if ($num < 20) {
                $str .= $ones[$num];
            } else {
                $str .= $tens[intval($num / 10)];
                if ($num % 10 > 0) {
                    $str .= '-' . $ones[$num % 10];
                }
            }
        }
        return trim($str);
    };

    $words = '';
    if ($rupees > 0) {
        $words .= $numberToWords($rupees);
    }
    if ($cents > 0) {
        if ($rupees > 0) {
            $words .= ' and ';
        }
        $words .= $numberToWords($cents) . ' cents';
    }
    if ($words === '') {
        $words = 'zero';
    }
    return ucfirst(trim($words));
}

// Get default tax rate
$taxQuery = "SELECT `rate` FROM `tbl_tax` LIMIT 1";
$taxResult = $conn->query($taxQuery);
$defaultTax = 18;
if ($taxResult && $taxResult->num_rows > 0) {
    $taxRow = $taxResult->fetch_assoc();
    $defaultTax = $taxRow['rate'];
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

$recordID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($recordID <= 0) {
    $dompdf->loadHtml('<h3>Invalid order id</h3>');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    if (ob_get_length()) ob_end_clean();
    $dompdf->stream("InvalidOrder.pdf", ["Attachment" => 0]);
    exit;
}

$isDuplicatePrint = false;
$sqlPrintStatus = "SELECT `is_printed` FROM `tbl_customer_order` WHERE `idtbl_customer_order`='$recordID' LIMIT 1";
$resultPrintStatus = $conn->query($sqlPrintStatus);
if ($resultPrintStatus && $resultPrintStatus->num_rows > 0) {
    $rowPrintStatus = $resultPrintStatus->fetch_assoc();
    $isDuplicatePrint = (isset($rowPrintStatus['is_printed']) && (int)$rowPrintStatus['is_printed'] === 1);
}

$conn->query("UPDATE `tbl_customer_order` SET `is_printed`='1' WHERE `idtbl_customer_order`='$recordID'");

$sqlporderinfo = "SELECT `o`.`cuspono`, `i`.`invoiceno`,`i`.`date` AS 'invoicedate', `o`.`discount`, `o`.`podiscount`,
    `o`.`podiscountpercentage`, `o`.`vat`, `o`.`confirm`, `o`.`dispatchissue`, `o`.`delivered`,
    `o`.`remark`, `o`.`idtbl_customer_order`, `o`.`date`, `o`.`total`, `o`.`delivereddatetime`,
    `l`.`idtbl_locations`, `l`.`locationname`, `c`.`customer`, `c`.`vat_num`,
    `c`.`address`, `c`.`phone` AS 'customerphone', `e`.`name` AS `saleref`,
    `e`.`phone`, `a`.`area`, `u`.`name` as `username`, `o`.`tbl_customer_idtbl_customer`
FROM `tbl_customer_order` AS `o`
LEFT JOIN `tbl_invoice` AS `i` ON `i`.`tbl_customer_order_idtbl_customer_order` = `o`.`idtbl_customer_order`
LEFT JOIN `tbl_customer` AS `c` ON `c`.`idtbl_customer` = `o`.`tbl_customer_idtbl_customer`
LEFT JOIN `tbl_locations` AS `l` ON `l`.`idtbl_locations` = `o`.`tbl_locations_idtbl_locations`
LEFT JOIN `tbl_employee` AS `e` ON `e`.`idtbl_employee`=`o`.`tbl_employee_idtbl_employee`
LEFT JOIN `tbl_area` AS `a` ON `a`.`idtbl_area`=`o`.`tbl_area_idtbl_area`
LEFT JOIN `tbl_user` AS `u` ON `u`.`idtbl_user`=`o`.`tbl_user_idtbl_user`
WHERE `o`.`status`=1 AND `o`.`idtbl_customer_order`='$recordID'";

$resultporderinfo = $conn->query($sqlporderinfo);
$rowporderinfo = $resultporderinfo->fetch_assoc();

if (!$rowporderinfo) {
    $dompdf->loadHtml('<h3>Purchase order not found</h3>');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    if (ob_get_length()) ob_end_clean();
    $dompdf->stream("PurchaseOrderNotFound.pdf", ["Attachment" => 0]);
    exit;
}

$customerID = $rowporderinfo['tbl_customer_idtbl_customer'];
$customerPhone = $rowporderinfo['customerphone'];
$porderDate = $rowporderinfo['invoicedate'];
$deliverDate = !empty($rowporderinfo['delivereddatetime']) ? $rowporderinfo['delivereddatetime'] : null;
$customername = $rowporderinfo['customer'];
$location = $rowporderinfo['locationname'];
$customeraddress = $rowporderinfo['address'];
$poderId = $rowporderinfo['idtbl_customer_order'];
$remark = $rowporderinfo['remark'];
$invoiceno = $rowporderinfo['invoiceno'];
$cuspono = $rowporderinfo['cuspono'];
$confirm = $rowporderinfo['confirm'];
$dispatchissue = $rowporderinfo['dispatchissue'];
$delivered = $rowporderinfo['delivered'];
$vat_num = trim($rowporderinfo['vat_num'] ?? '');
$isTaxCustomer = !empty($vat_num);
$actualvat = isset($rowporderinfo['vat']) && $rowporderinfo['vat'] > 0 ? $rowporderinfo['vat'] : $defaultTax;

$qtyflag = 0;
if ($confirm == 1 && ($dispatchissue == null || $dispatchissue == 0) && ($delivered == null || $delivered == 0)) {
    $qtyflag = 1;
} else if ($confirm == 1 && $dispatchissue == 1 && ($delivered == null || $delivered == 0)) {
    $qtyflag = 2;
} else if ($confirm == 1 && $dispatchissue == 1 && $delivered == 1) {
    $qtyflag = 3;
}

$sqlporderdetail = "
SELECT p.product_name, p.product_code, p.idtbl_product, d.orderqty, d.confirmqty,
       d.discount, d.dispatchqty, d.qty, d.saleprice
FROM tbl_customer_order_detail AS d
LEFT JOIN tbl_product AS p ON p.idtbl_product = d.tbl_product_idtbl_product
WHERE d.tbl_customer_order_idtbl_customer_order = '$recordID' AND d.status = 1
ORDER BY p.product_name ASC";

$resultporderdetail = $conn->query($sqlporderdetail);

$modeOfPayment = '';
if ($isTaxCustomer) {
    $sqlpaymethod = "SELECT
            GROUP_CONCAT(DISTINCT
                CASE `pd`.`method`
                    WHEN 1 THEN 'Cash'
                    WHEN 2 THEN 'Bank / Cheque'
                    WHEN 3 THEN 'Online'
                    ELSE 'Unknown'
                END
            ) AS payment_methods
        FROM `tbl_invoice_payment_detail` AS `pd`
        LEFT JOIN `tbl_invoice_payment_has_tbl_invoice` AS `piv`
            ON `piv`.`tbl_invoice_payment_idtbl_invoice_payment` = `pd`.`tbl_invoice_payment_idtbl_invoice_payment`
        LEFT JOIN `tbl_invoice` AS `inv`
            ON `inv`.`idtbl_invoice` = `piv`.`tbl_invoice_idtbl_invoice`
        WHERE `inv`.`tbl_customer_order_idtbl_customer_order` = '$recordID'
            AND `pd`.`status` = 1";
    $resultpaymethod = $conn->query($sqlpaymethod);
    if ($resultpaymethod && $resultpaymethod->num_rows > 0) {
        $rowpaymethod = $resultpaymethod->fetch_assoc();
        $modeOfPayment = $rowpaymethod['payment_methods'] ?? '';
    }
}

$html = '';

// ====================== TAX INVOICE (VAT REGISTERED) ======================
if ($isTaxCustomer) {
    $html .= '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>ENOMAX Holdings (PVT) LTD - Tax Invoice</title>
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; margin:0; padding:0; }
            table.invoice-table { width:100%; border-collapse: collapse; }
            table.invoice-table th, table.invoice-table td { border:1px solid black; padding:6px 5px; vertical-align:top; }
            table.invoice-table th { background-color:#f2f2f2; text-align:center; font-weight:bold; }
            table.no-border td { border: none; padding: 2px 5px; }
            .right { text-align:right; }
            .center { text-align:center; }
            .words { font-weight:bold; font-size:13.5px; }
            tr { page-break-inside: avoid; }
            @page { margin: 15mm 10mm 15mm 10mm; }
        </style>
    </head>
    <body>';

    $html .= '
        <table class="no-border" style="width:100%;">
            <tr>
                <td width="25%">&nbsp;</td>
                <td width="50%" style="text-align:center;">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr><td style="border:2px solid black;text-align:center;padding:8px;"><strong style="font-size:16px;">Tax Invoice</strong></td></tr>
                    </table>
                </td>
                <td width="25%" style="text-align:right;vertical-align:bottom;">';
    if ($isDuplicatePrint) {
        $html .= '<strong style="color:red;">DUPLICATE</strong>';
    }
    $html .= '</td></tr></table>';

    $html .= '
        <table class="invoice-table" style="margin-top:8px;">
            <tr>
                <td width="50%"><strong>Date of Invoice:</strong> ' . date('m/d/Y', strtotime($porderDate)) . '</td>
                <td width="50%"><strong>Tax Invoice No.:</strong> ' . $invoiceno . '</td>
            </tr>
            <tr>
                <td style="height:110px;">
                    <strong>Supplier\'s TIN:</strong> 109575771-7000<br>
                    <strong>Supplier\'s Name:</strong> ENOMAX HOLDINGS (PVT) LTD<br>
                    <strong>Address:</strong> No.46, Garden City, Minuwangoda Road, Ja-ela.<br><br>
                    <strong>Telephone No:</strong> 011 3468568
                </td>
                <td>
                    <strong>Purchaser\'s TIN:</strong> ' . $vat_num . '<br>
                    <strong>Purchaser\'s Name:</strong> ' . htmlspecialchars($customername) . '<br>
                    <strong>Address:</strong> ' . htmlspecialchars($customeraddress) . '<br>
                    <strong>Sales Person\'s Name:</strong> ' . $rowporderinfo['saleref'] . '<br><br>
                    <strong>Telephone No:</strong> ' . $customerPhone . '
                </td>
            </tr>
            <tr>
                <td><strong>Date of Delivery:</strong> ' . (!empty($deliverDate) ? date('m/d/Y', strtotime($deliverDate)) : ' ') . '</td>
                <td><strong>Place of Supply:</strong> ' . htmlspecialchars($location) . '</td>
            </tr>
            <tr>
                <td colspan="2" style="height:60px;">
                    <strong>Additional Information if any: </strong> ' . htmlspecialchars($remark) . '
                </td>
            </tr>
        </table>

        <table class="invoice-table" style="margin-top:8px;">
            <thead>
                <tr>
                    <th style="width:5%;">Reference</th>
                    <th style="text-align:left;">Description of Goods or Services</th>
                    <th style="width:8%;">Quantity</th>
                    <th style="width:12%;">Unit Price</th>
                    <th style="width:15%;">Amount<br>Excluding VAT<br>(Rs.)</th>
                </tr>
            </thead>
            <tbody>';

    $fulltot = 0;
    $itemCount = 0;
    $count = 0;

    while ($rowporderdetail = $resultporderdetail->fetch_assoc()) {
        $count++;
        $itemCount++;

        $qtyValue = 0;
        if ($qtyflag == 0) $qtyValue = $rowporderdetail['orderqty'];
        else if ($qtyflag == 1) $qtyValue = $rowporderdetail['confirmqty'];
        else if ($qtyflag == 2) $qtyValue = $rowporderdetail['dispatchqty'];
        else if ($qtyflag == 3) $qtyValue = $rowporderdetail['qty'];

        $base_price = $rowporderdetail['saleprice'] / (1 + ($actualvat / 100));
        $base_discount = $rowporderdetail['discount'] / (1 + ($actualvat / 100));
        $line_total_base = ($qtyValue * $base_price) - $base_discount;
        $fulltot += $line_total_base;

        $html .= '
                <tr>
                    <td class="center">' . $count . '</td>
                    <td>' . htmlspecialchars($rowporderdetail['product_code'] . ' - ' . $rowporderdetail['product_name']) . '</td>
                    <td class="center">' . $qtyValue . '</td>
                    <td class="right">' . number_format($base_price, 2) . '</td>
                    <td class="right">' . number_format($line_total_base, 2) . '</td>
                </tr>';
    }

    // ====================== FIXED VAT CALCULATION ======================
    $subtotal_before_discount = $fulltot;
    $po_discount_amount = $subtotal_before_discount * ($rowporderinfo["podiscountpercentage"] / 100);
    $net_total_before_vat = $subtotal_before_discount - $po_discount_amount;
    $vat_amount = $net_total_before_vat * ($actualvat / 100);
    $grand_total = $net_total_before_vat + $vat_amount;

    $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right">Item Count:</td>
                    <td class="right"><strong>' . $itemCount . '</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="right">Total Value of Supply:</td>
                    <td class="right"><strong>' . number_format($subtotal_before_discount, 2) . '</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="right">Discount:</td>
                    <td class="right"><strong>' . number_format($po_discount_amount, 2) . '</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="right">Net Total before VAT:</td>
                    <td class="right"><strong>' . number_format($net_total_before_vat, 2) . '</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="right">VAT Amount (Total Value of Supply @ ' . $actualvat . '%)</td>
                    <td class="right"><strong>' . number_format($vat_amount, 2) . '</strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="right">Total Amount including VAT:</td>
                    <td class="right"><strong>' . number_format($grand_total, 2) . '</strong></td>
                </tr>
            </tfoot>
        </table>

        <table class="invoice-table" style="margin-top:8px;">
            <tr>
                <td style="padding:8px;">
                    <strong>Total Amount in words: </strong>
                    <span class="words">' . ConvertRupeeToText($grand_total) . '</span>
                </td>
            </tr>
            <tr>
                <td style="padding:8px;">
                    <strong>Mode of Payment: </strong> ' . htmlspecialchars($modeOfPayment) . '
                </td>
            </tr>
        </table>

        <table width="100%" class="no-border" style="margin-top:30px;">
            <tr>
                <td colspan="2">Received the above goods in good condition</td>
                <td colspan="4">&nbsp;</td>
            </tr>
            <tr>
                <td>Received By</td>
                <td>..............</td>
                <td>&nbsp;</td>
                <td align="center">..............</td>
                <td align="center">..............</td>
                <td align="center">..............</td>
            </tr>
            <tr>
                <td>Date &amp; Time</td>
                <td>..............</td>
                <td>&nbsp;</td>
                <td align="center">Prepared By</td>
                <td align="center">Authorized By</td>
                <td align="center">Taken Out By</td>
            </tr>
        </table>
    </body>
    </html>';

    // ====================== NON-VAT INVOICE (ORIGINAL FORMAT, RESTORED) ======================
} else {

    $html .= '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ENOMAX Holdings (PVT) LTD</title>
        <style>
            * {
            font-size: 10px;
            margin: 0.2px;
            font-family: "DejaVu Sans", sans-serif;
            }
            #detailtable {
                width: 100%;
                border-collapse: collapse;
                padding-top: 0.2cm;
                border: 1px solid black;
            }
            #detailtable th, #detailtable td { 
                border: 1px solid black;
                padding: 5px;
            }
            #detailth {
                background-color: #f2f2f2;
                text-align: left;
                border: 1px solid black; 
            }
            #detailtd {
                border: 1px solid black; 
            }
            .page-total {
                background-color: #f9f9f9;
                font-weight: bold;
            }

            #tablefooter {
                width: 100%;
            }

            .leftboxtop {
                padding-left: 0.3cm;
            }
            .rightboxtop {
                padding-right: 0.3cm;
            }

            th {
                background-color: #f2f2f2;
                font-size: 12px;
                text-align: left;
                padding: 5px;
            }

            td {
                padding: 5px;
                vertical-align: top;
                font-size: 12px;
            }
            .tdheader {
                padding: 5px;
                vertical-align: top;
                font-size: 20px;
            }

            .left-align {
                text-align: left;
            }

            .right-align {
                text-align: right;
            }

            .center-align {
                text-align: center;
            }

            .spacer {
                height: 1.8cm;
            }
           
        </style>
    </head>
    <body style="height:14cm">

    <header>
        <table border="0" width="100%">
            <tr>
                <td colspan="3" height="1.8cm"></td>
            </tr>
            <tr>
                <td class="leftboxtop" width="100%">
                    <table border="0" width="100%" style="margin-top:-43; padding-left:0.3cm;">
                        <tr>
                            <td>
                                <h3 style="font-weight: bold; font-size: 20px; margin: 0;">ENOMAX HOLDINGS (PVT) LTD</h3>
                                <h4 style="font-size: 16px; margin-top: 0.2cm;">No.46, Garden City, Minuwangoda Road, Ja-ela.</h4>
                                <h4>Tel: 011 3468568</h4>
                            </td>
                            <td align="right" valign="top">';
    if ($isDuplicatePrint) {
        $html .= '<h4 style="font-size: 12px; font-weight: bold; margin: 0;">DUPLICATE</h4>';
    }
    $html .= '&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table border="0" width="100%">
            <tr>
                <td colspan="3" height="1.8cm"></td>
            </tr>
            <tr>
                <td class="leftboxtop" width="10cm">
                    <table border="0" width="100%" style="margin-top:-70; padding-left:0.3cm;">
                        <tr>
                            <th>Customer Details - ' . $customerID . '</th>
                        </tr>
                        <tr>
                            <td>' . $customername . '<br><br>' . $customeraddress . '<br><br>Tel : ' . $customerPhone . '<br><br>Date : ' . $porderDate . '</td>
                        </tr>
                    </table>
                </td>
                <td style="padding-left:100px;" width="8cm">
                    <table width="100%" height="100%" style="margin-top:-70;" border="0">
                        <tr>
                            <th align="center" colspan="2">Purchase Order Details - ' . $cuspono . '</th>
                        </tr>
                        <tr><td align="left" style="font-weight: bold;">Invoice No </td><td>' . $invoiceno . ' </td></tr>
                        <tr><td align="left" style="font-weight: bold;">LOCATION </td><td>' . $location . ' </td></tr>
                        <tr><td align="left" style="font-weight: bold;">EMPLOYEE </td><td>' . $rowporderinfo['saleref'] . ' </td></tr>
                        <tr><td align="left" style="font-weight: bold;">CONTACT </td><td>' . $rowporderinfo['phone'] . ' </td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </header>

    <main>
        <div class="" style="margin-top:15px;">
            <table width="100%" style="padding-left:1cm;padding-right:1cm; padding-top:0.4cm;" id="detailtable">
            <thead>
                <tr>
                    <th id="detailth">#</th>
                    <th id="detailth">Code</th>
                    <th id="detailth">Product Name</th>
                    <th id="detailth" align="right">Quantity</th>
                    <th id="detailth" align="right">Sale Price</th>
                    <th id="detailth" align="right">Discount</th>
                    <th id="detailth" align="right">Total</th>
                </tr>
            </thead>
            ';
    $rowCount = mysqli_num_rows($resultporderdetail);
    $itemCount = 0;
    $count = 0;
    $count1 = 0;
    $fulltot = 0;
    $newtemp = 0;

    while ($rowporderdetail = $resultporderdetail->fetch_assoc()) {

        $count = $count + 1;
        $count1++;
        $itemCount++;

        $qtyValue = 0;
        if ($qtyflag == 0) {
            $qtyValue = $rowporderdetail['orderqty'];
        } else if ($qtyflag == 1) {
            $qtyValue = $rowporderdetail['confirmqty'];
        } else if ($qtyflag == 2) {
            $qtyValue = $rowporderdetail['dispatchqty'];
        } else if ($qtyflag == 3) {
            $qtyValue = $rowporderdetail['qty'];
        }

        // Non-VAT customers use price as-is
        $base_price = $rowporderdetail['saleprice'];
        $base_discount = $rowporderdetail['discount'];

        $line_total_base = ($qtyValue * $base_price) - $base_discount;
        $fulltot += $line_total_base;

        $html .= '
                    <tr>
                        <td id="detailtd">' . $count1 . '</td>
                        <td id="detailtd">' . $rowporderdetail['product_code'] . '</td>
                        <td id="detailtd">' . $rowporderdetail['product_name'] . '</td>
                        <td id="detailtd" align="right">' . $qtyValue . '</td>
                        <td id="detailtd" align="right">' . number_format($base_price, 2) . '</td>
                        <td id="detailtd" align="right">' . number_format($base_discount, 2) . '</td>
                        <td id="detailtd" align="right">' . number_format($line_total_base, 2) . '</td>
                    </tr>
                ';
        $temptotal = $qtyValue * $base_price;
        $newtemp += $temptotal;
        if ($count1 % 28 == 0) {
            $html .= '
                        <tr class="page-total">
                            <td colspan="6" id="detailtd">Page Total</td>
                            <td id="detailtd" align="right">' . number_format($newtemp, 2) . '</td>
                        </tr>
                    ';
            $newtemp = 0;
        }
    }
    $html .= '
            </table> 
            ';

    if ($resultporderdetail->num_rows == $count) {
        $html .= '
                    <footer>
                        <div style="">
                            <table border="0" width="100%">
                                <tr>
                                    <td class="leftboxtop" width="10cm">
                                        <table border="0" width="100%" style="padding-left:0.5cm;">
                                            <tr>
                                                <th>REMARKS</th>
                                            </tr>
                                            <tr>
                                                <td>' . $remark . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="8cm">
                                        <table border="0" id="tablefooter" style="margin-right:35px">';

        // NON-VAT CUSTOMER CALCULATION
        $po_discount_amount = $fulltot * ($rowporderinfo["podiscountpercentage"] / 100);
        $net_total = $fulltot - $po_discount_amount;

        $html .= '
                                        <tr>
                                            <td align="right" style="font-weight: bold;">Item Count:</td>
                                            <td align="right">' . $itemCount . '</td>
                                        </tr>
                                        <tr>
                                            <td align="right" style="font-weight: bold;">Net Total:</td>
                                            <td align="right">' . number_format($fulltot, 2) . '</td>
                                        </tr>
                                        <tr>
                                            <td align="right" style="font-weight: bold;">Discount:</td>
                                            <td align="right" style="padding-top:0.2cm;">' . number_format($po_discount_amount, 2) . '</td>
                                        </tr>
                                        <tr>
                                            <td align="right" style="font-weight: bold;">Total:</td>
                                            <td align="right" style="padding-top:0.2cm;font-weight: bold;">' . number_format($net_total, 2) . '</td>
                                        </tr>';

        $html .= '
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <table width="100%">
								<tr>
									<td colspan="2">Received the above goods in good condition</td>
									<td colspan="4">&nbsp;</td>
								</tr>
								<tr>
									<td>Received By</td>
									<td>..............</td>
									<td>&nbsp;</td>
									<td align="center">..............</td>
									<td align="center">..............</td>
									<td align="center">..............</td>
								</tr>
								<tr>
									<td>Date &amp; Time</td>
									<td>..............</td>
									<td>&nbsp;</td>
									<td align="center">Prepared By</td>
									<td align="center">Authorized By</td>
									<td align="center">Taken Out By</td>
								</tr>
							</table>
                        </div>
                    </footer>';
    }
    $html .= '  
        </div>
        
    </main>
    </body>
    </html>';
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if (ob_get_length()) {
    ob_end_clean();
}

$filename = $isTaxCustomer ? "TaxInvoice_" . $invoiceno . ".pdf" : "Invoice_" . $invoiceno . ".pdf";
$dompdf->stream($filename, ["Attachment" => 0]);
