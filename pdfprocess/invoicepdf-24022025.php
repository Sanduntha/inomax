<?php
session_start();
require_once('../connection/db.php');
require_once '../vendor/autoload.php'; // Adjust the path as necessary

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

$sqlinvoiceinfo = "SELECT `tbl_invoice`.`discount`, `tbl_invoice`.`idtbl_invoice`, `tbl_invoice`.`invoiceno`, `tbl_invoice`.`date`, `tbl_invoice`.`total`, `tbl_invoice`.`paymentcomplete`, `tbl_locations`.`idtbl_locations`, `tbl_locations`.`locationname`, `tbl_customer`.`name`, `tbl_customer`.`address`, `tbl_customer`.`phone`, `tbl_employee`.`name` AS `saleref`, `tbl_employee`.`phone` AS 'salesrepphone', `tbl_area`.`area`, `tbl_user`.`name` as `username`, `tbl_invoice`.`tbl_customer_idtbl_customer`, `tbl_customer_order`.`cuspono` FROM `tbl_invoice` LEFT JOIN `tbl_locations` ON `tbl_locations`.`idtbl_locations`=`tbl_invoice`.`tbl_locations_idtbl_locations` LEFT JOIN `tbl_customer` ON `tbl_customer`.`idtbl_customer`=`tbl_invoice`.`tbl_customer_idtbl_customer` LEFT JOIN `tbl_customer_order` ON `tbl_customer_order`.`idtbl_customer_order`=`tbl_invoice`.`tbl_customer_order_idtbl_customer_order` LEFT JOIN `tbl_employee` ON `tbl_employee`.`idtbl_employee`=`tbl_customer_order`.`tbl_employee_idtbl_employee` LEFT JOIN `tbl_area` ON `tbl_area`.`idtbl_area`=`tbl_invoice`.`tbl_area_idtbl_area` LEFT JOIN `tbl_user` ON `tbl_user`.`idtbl_user`=`tbl_invoice`.`tbl_user_idtbl_user`WHERE `tbl_invoice`.`status`=1 AND `tbl_invoice`.`idtbl_invoice`='$recordID'";
$resultinvoiceinfo = $conn->query($sqlinvoiceinfo);
$rowinvoiceinfo = $resultinvoiceinfo->fetch_assoc();

$customerID = $rowinvoiceinfo['tbl_customer_idtbl_customer'];
$customerPhone = $rowinvoiceinfo['phone'];
$customername = $rowinvoiceinfo['name'];
$location = $rowinvoiceinfo['locationname'];
$customeraddress = $rowinvoiceinfo['address'];
$paymentcomplete = $rowinvoiceinfo['paymentcomplete'];
$invoID = $rowinvoiceinfo['idtbl_invoice'];
$invoiceno = $rowinvoiceinfo['invoiceno']; 
$pono = $rowinvoiceinfo['cuspono']; 
$salesrepphone = $rowinvoiceinfo['salesrepphone']; 


// $sqlpoID = "SELECT `idtbl_porder_invoice` FROM `tbl_porder_invoice` WHERE `tbl_invoice_idtbl_invoice` = '$invoID'";
// $resultpoID = $conn->query($sqlpoID);
// $rowpoID = $resultpoID->fetch_assoc();

$sqlinvoicedetail = "SELECT `tbl_product`.`product_name`, `tbl_product`.`product_code`, `tbl_product`.`idtbl_product`, `tbl_invoice_detail`.`qty`, `tbl_invoice_detail`.`saleprice`, `tbl_invoice_detail`.`discount` FROM `tbl_invoice_detail` LEFT JOIN `tbl_product` ON `tbl_product`.`idtbl_product`=`tbl_invoice_detail`.`tbl_product_idtbl_product` WHERE `tbl_invoice_detail`.`tbl_invoice_idtbl_invoice`='$recordID' AND `tbl_invoice_detail`.`status`=1";
$resultinvoicedetail = $conn->query($sqlinvoicedetail);

$sqlinvoiceoutstanding = "SELECT `tbl_employee`.`name` as `asm`, `tbl_invoice`.`idtbl_invoice`, `tbl_invoice`.`paymentcomplete`, `tbl_invoice`.`date`, `tbl_invoice`.`total`, SUM(`tbl_invoice_payment_has_tbl_invoice`.`payamount`) AS `payamount` FROM `tbl_invoice` LEFT JOIN `tbl_invoice_payment_has_tbl_invoice` ON `tbl_invoice_payment_has_tbl_invoice`.`tbl_invoice_idtbl_invoice`=`tbl_invoice`.`idtbl_invoice` LEFT JOIN `tbl_employee` ON `tbl_employee`.`idtbl_employee` = `tbl_invoice`.`ref_id` WHERE `tbl_invoice`.`tbl_customer_idtbl_customer`='$customerID' AND `tbl_invoice`.`status`=1 AND `tbl_invoice`.`paymentcomplete`=0 AND `tbl_invoice`.`payment_created` IN (0,1) AND `tbl_invoice`.`idtbl_invoice` != '$recordID' Group BY `tbl_invoice`.`idtbl_invoice`";
$resultinvoiceoutstanding = $conn->query($sqlinvoiceoutstanding);

$sqlinvoicedetailfree = "SELECT `tbl_product`.`product_name`, `tbl_invoice_detail`.`freeqty` FROM `tbl_invoice_detail` LEFT JOIN `tbl_product` ON `tbl_product`.`idtbl_product`=`tbl_invoice_detail`.`freeproductid` WHERE `tbl_invoice_detail`.`tbl_invoice_idtbl_invoice`='$recordID' AND `tbl_invoice_detail`.`status`=1 AND `tbl_invoice_detail`.`freeqty`>0";
$resultinvoicedetailfree = $conn->query($sqlinvoicedetailfree);

$html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ENOMAX Holdings (PVT) LTD</title>
        <style>
            *{
                font-size: 10;
                margin:0.2px;
                font-family: \'San-Serif\', sans-serif;
            }
            header {
                position: fixed;
                top: 0cm;
                left: 0cm;
                right: 0cm;
                height:6cm;
            }
            footer {
                position: fixed; 
                bottom: 0cm; 
                left: 0cm; 
                right: 0.3cm;
                bottom: 7.1cm;
            }
            .leftboxtop{
                width:10.5cm;
                height:4.25cm;
            }
            .bottomtop{
                width:2.5cm;
            }
            .righttop{
                width:7.5cm;
            }
            .bottomtable{
                height:13.5cm;
            }
            .divclass{
                border-right:1px solid black;
            }
            .listView tr {
                line-height: 1;
            }
        </style>
    </head>
    <body style="margin-top:7cm; margin-bottom:5cm; height:14cm">

    <header>
        <table border="0" width="100%">
            <tr>
                <td colspan="3" height="1.8cm"></td>
            </tr>
            <tr>
                <td class="leftboxtop" width="10cm">
                    <table border="0" width="100%" style="margin-top:-43; padding-left:0.3cm;">
                        <tr>
                            <td>Customer ID : ' . $customerID . '<br><span  style="font-size: 1.2em; font-weight: bold;">' . $customername . '</span><br>' . $customeraddress . '<br>Tel : ' . $customerPhone . '</td>
                        </tr>
                    </table>
                </td>
                <td width="3cm">&nbsp;</td>
                <td>
                    <table width="100%" height="100%" border="0">
                        <tr><td width="55%" height="0.5cm"> </td><td align="left">' .  $rowinvoiceinfo['date'] . ' </td></tr>
                        <tr><td height="0.5cm"></td> <td align="left">' . $invoiceno . '</td></tr>
                        <tr><td height="0.5cm"></td> <td align="left">' . $pono . '</td></tr>
                        <tr><td height="0.5cm"></td> <td align="left">'.$location.'</td></tr>
                        <tr><td height="0.5cm"></td> <td align="left">' . $rowinvoiceinfo['saleref'] . '</td></tr>
                        <tr><td height="0.5cm"></td> <td align="left">' . $salesrepphone . '</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </header>

    <main>
        <div class="">
            <table class="listView" width="100%" style="padding-left:0.3cm; padding-right:1cm; padding-top:0.2cm;">
            ';
            $rowCount = mysqli_num_rows($resultinvoicedetail);
            $count = 0;
            $count1 = 0;

            while ($rowinvoicedetail = $resultinvoicedetail->fetch_assoc()) {
                $totnew = $rowinvoicedetail['qty'] * $rowinvoicedetail['saleprice'];
                $fulltot += $totnew;
                $count = $count + 1;
                $count1++;
                $html .= '
                    <tr>
                        <td style="width:2.3cm;">' . $count .' ' . $rowinvoicedetail['product_code'] . '</td>
                        <td style="width:8.86cm;">' . $rowinvoicedetail['product_name'] . '</td>
                        <td style="width:1.4cm;" align="center">' . $rowinvoicedetail['qty'] . '</td>
                        <td style="width:2.5cm;" align="right">' . number_format($rowinvoicedetail['saleprice'], 2) . '</td>
                        <td style="width:1.3cm;" align="right">' . number_format($rowinvoicedetail['discount'], 2) . '</td>
                        <td style="width:2.6cm;" align="right">' . number_format((($rowinvoicedetail['saleprice'] * $rowinvoicedetail['qty']) - $rowinvoicedetail['discount']), 2) . '</td>
                    </tr>
                ';
                $temptotal = $rowinvoicedetail['qty'] * $rowinvoicedetail['saleprice'];
                $newtemp += $temptotal;
                if ($count1 % 25 == 0) {
                    $html .= '
                        <tr>
                            <td colspan="5">This page Total Showing here. See the Next page Thank You</td>
                            <td style="width:2.6cm;" align="right">' . number_format($newtemp, 2) . '</td>
                        </tr>
                        </table>
                        <div style="page-break-before: always;"></div>
                        <table class="listView" width="100%" style="padding-left:0.3cm; padding-right:1cm; padding-top:0.2cm;">
                    ';
                    $newtemp = 0;
                }
            }
            $html .= '
            </table> 
            ';

            if ($resultinvoicedetail->num_rows == $count) {
                $html .= '
                    <footer>
                        <div style="margin-top: -0.1cm;margin-right: -1.7cm; padding-right: 2.5cm;">
                            <table width="100%" height="100%" style="border-collapse: collapse;" border="0">
                            ';
                                $discount = $rowinvoiceinfo["discount"];
                                $net_total = $fulltot - $discount;

                                $html .= '
                                <tr>
                                    <td align="right">' . number_format($fulltot, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="right" style="padding-top:0.2cm;">' . number_format($discount, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="right" style="padding-top:0.2cm;font-weight: bold;">' . number_format($net_total, 2) . '</td>
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

$dompdf->loadHtml($html);
$dompdf->setPaper('21.5cm', '27.5cm', 'portrait');
$dompdf->render();
$dompdf->stream("Test.pdf", ["Attachment" => 0]);
