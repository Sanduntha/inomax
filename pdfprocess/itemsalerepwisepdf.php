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

$validfrom = isset($_GET['validfrom']) ? $_GET['validfrom'] : '';
$validto   = isset($_GET['validto'])   ? $_GET['validto']   : '';
$productID = isset($_GET['product'])   ? intval($_GET['product'])  : 0;
$repID     = isset($_GET['rep'])       ? intval($_GET['rep'])      : 0;
$areaID    = isset($_GET['area'])      ? intval($_GET['area'])     : 0;

/* ── Fetch data ── */
if ($productID > 0) {
    $sql = "SELECT
                `ue`.`name`                                      AS `repname`,
                `p`.`product_name`,
                SUM(`d`.`qty`)                                   AS `total_qty`,
                SUM(`d`.`total`)                                 AS `total_amount`,
                COUNT(DISTINCT `u`.`idtbl_customer_order`)       AS `order_count`
            FROM `tbl_customer_order_detail` AS `d`
            INNER JOIN `tbl_customer_order`  AS `u`  ON `d`.`tbl_customer_order_idtbl_customer_order` = `u`.`idtbl_customer_order`
            INNER JOIN `tbl_product`         AS `p`  ON `d`.`tbl_product_idtbl_product`               = `p`.`idtbl_product`
            LEFT  JOIN `tbl_employee`        AS `ue` ON `u`.`tbl_employee_idtbl_employee`             = `ue`.`idtbl_employee`
            LEFT  JOIN `tbl_area`            AS `ub` ON `u`.`tbl_area_idtbl_area`                     = `ub`.`idtbl_area`
            WHERE `u`.`date`   BETWEEN '$validfrom' AND '$validto'
            AND   `u`.`status` = 1
            AND   `d`.`tbl_product_idtbl_product` = '$productID'";

    if ($repID  > 0) $sql .= " AND `u`.`tbl_employee_idtbl_employee` = '$repID'";
    if ($areaID > 0) $sql .= " AND `u`.`tbl_area_idtbl_area`         = '$areaID'";
    $sql .= " GROUP BY `ue`.`idtbl_employee` ORDER BY `total_qty` DESC";

    $singleProduct = true;

    $sqlpname    = "SELECT `product_name` FROM `tbl_product` WHERE `idtbl_product` = '$productID'";
    $rpname      = $conn->query($sqlpname)->fetch_assoc();
    $productName = $rpname ? htmlspecialchars($rpname['product_name']) : '';

} else {
    $sql = "SELECT
                `ue`.`name`                                      AS `repname`,
                `p`.`product_name`,
                SUM(`d`.`qty`)                                   AS `total_qty`,
                SUM(`d`.`total`)                                 AS `total_amount`,
                COUNT(DISTINCT `u`.`idtbl_customer_order`)       AS `order_count`
            FROM `tbl_customer_order_detail` AS `d`
            INNER JOIN `tbl_customer_order`  AS `u`  ON `d`.`tbl_customer_order_idtbl_customer_order` = `u`.`idtbl_customer_order`
            INNER JOIN `tbl_product`         AS `p`  ON `d`.`tbl_product_idtbl_product`               = `p`.`idtbl_product`
            LEFT  JOIN `tbl_employee`        AS `ue` ON `u`.`tbl_employee_idtbl_employee`             = `ue`.`idtbl_employee`
            LEFT  JOIN `tbl_area`            AS `ub` ON `u`.`tbl_area_idtbl_area`                     = `ub`.`idtbl_area`
            WHERE `u`.`date`   BETWEEN '$validfrom' AND '$validto'
            AND   `u`.`status` = 1";

    if ($repID  > 0) $sql .= " AND `u`.`tbl_employee_idtbl_employee` = '$repID'";
    if ($areaID > 0) $sql .= " AND `u`.`tbl_area_idtbl_area`         = '$areaID'";
    $sql .= " GROUP BY `ue`.`idtbl_employee`, `p`.`idtbl_product` ORDER BY `total_qty` DESC";

    $singleProduct = false;
    $productName   = 'All Products';
}

$result      = $conn->query($sql);
$rows        = [];
$grandQty    = 0;
$grandAmount = 0;
$grandOrders = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[]       = $row;
        $grandQty    += $row['total_qty'];
        $grandAmount += $row['total_amount'];
        $grandOrders += $row['order_count'];
    }
}

/* ── Build HTML ── */
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ENOMAX Holdings (PVT) LTD - Item Sale by Rep Report</title>
<style>
@page {
    margin: 1cm 0.8cm 1cm 0.8cm;
}
body {
    font-family: Arial, sans-serif;
    font-size: 9px;
    margin-top: 2.5cm;
}
.header-box {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background-color: #005EB8;
    color: #FFFFFF;
    padding: 0.3cm;
    border-bottom: 2px solid #000;
    text-align: center;
    height: 1.3cm;
}
.company-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 0.1cm;
}
.company-info {
    font-size: 8px;
}
.watermark {
    position: fixed;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 72px;
    color: rgba(0, 94, 184, 0.06);
    font-weight: bold;
    z-index: -1;
    text-align: center;
    width: 100%;
}
.footer-box {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    border-top: 1px solid #005EB8;
    padding-top: 0.1cm;
    font-size: 7.5px;
    color: #666;
    text-align: center;
}
.report-title-box {
    background-color: #005EB8;
    color: #FFFFFF;
    text-align: center;
    padding: 0.2cm;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 0.2cm;
}
.meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.3cm;
    font-size: 9px;
}
.meta-table td {
    padding: 0.1cm 0.2cm;
    border: 1px solid #ccc;
}
.meta-table td.label {
    font-weight: bold;
    width: 15%;
    background-color: #f0f4ff;
    color: #005EB8;
}
table.items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0.2cm;
    table-layout: fixed;
}
table.items th {
    background-color: #005EB8;
    color: #FFFFFF;
    font-weight: bold;
    padding: 0.15cm;
    border: 1px solid #000;
    font-size: 9px;
    text-align: center;
}
table.items td {
    border: 1px solid #ccc;
    padding: 0.12cm 0.15cm;
    font-size: 8.5px;
    word-wrap: break-word;
}
table.items tr.odd  { background-color: #ffffff; }
table.items tr.even { background-color: #f5f8ff; }
table.items tfoot td {
    background-color: #005EB8;
    color: #ffffff;
    font-weight: bold;
    border: 1px solid #003d7a;
    padding: 0.15cm;
    font-size: 9px;
}
</style>
</head>
<body>

<!-- Fixed header -->
<div class="header-box">
    <div class="company-name">ENOMAX HOLDINGS (PVT) LTD</div>
    <div class="company-info">
        No.46, Garden City, Minuwangoda Road, Ja-ela. &nbsp;&nbsp; Tel: 011 3468568
    </div>
</div>

<!-- Watermark -->
<div class="watermark">ENOMAX</div>

<!-- Fixed footer -->
<div class="footer-box">
    Generated on ' . date('Y-m-d H:i:s') . ' &nbsp;|&nbsp; Item Sale by Rep Report &nbsp;|&nbsp; ENOMAX HOLDINGS (PVT) LTD
</div>

<!-- Report title -->
<div class="report-title-box">ITEM SALE BY REP REPORT</div>

<!-- Meta info -->
<table class="meta-table">
    <tr>
        <td class="label">Period</td>
        <td>' . htmlspecialchars($validfrom) . ' &nbsp;to&nbsp; ' . htmlspecialchars($validto) . '</td>
        <td class="label">Product</td>
        <td>' . $productName . '</td>
    </tr>
</table>';

/* ── Items table ── */
$html .= '<table class="items">
<thead>
<tr>';

if ($singleProduct) {
    $html .= '
    <th style="width:6%;">#</th>
    <th style="width:44%; text-align:left;">Rep Name</th>
    <th style="width:15%;">Orders</th>
    <th style="width:15%;">Qty Sold</th>
    <th style="width:20%;">Amount (Rs.)</th>';
} else {
    $html .= '
    <th style="width:5%;">#</th>
    <th style="width:25%; text-align:left;">Rep Name</th>
    <th style="width:30%; text-align:left;">Product</th>
    <th style="width:12%;">Orders</th>
    <th style="width:12%;">Qty Sold</th>
    <th style="width:16%;">Amount (Rs.)</th>';
}

$html .= '</tr>
</thead>
<tbody>';

if (empty($rows)) {
    $colspan = $singleProduct ? 5 : 6;
    $html .= '<tr><td colspan="' . $colspan . '" style="text-align:center; color:red; padding:0.3cm;">No records found for the selected criteria.</td></tr>';
} else {
    $counter = 1;
    foreach ($rows as $row) {
        $rowClass = ($counter % 2 === 0) ? 'even' : 'odd';

        if ($singleProduct) {
            $html .= '
            <tr class="' . $rowClass . '">
                <td style="text-align:center;">' . $counter . '</td>
                <td>' . htmlspecialchars($row['repname']) . '</td>
                <td style="text-align:center;">' . $row['order_count'] . '</td>
                <td style="text-align:center;"><strong>' . number_format($row['total_qty']) . '</strong></td>
                <td style="text-align:right;">' . number_format($row['total_amount'], 2) . '</td>
            </tr>';
        } else {
            $html .= '
            <tr class="' . $rowClass . '">
                <td style="text-align:center;">' . $counter . '</td>
                <td>' . htmlspecialchars($row['repname']) . '</td>
                <td>' . htmlspecialchars($row['product_name']) . '</td>
                <td style="text-align:center;">' . $row['order_count'] . '</td>
                <td style="text-align:center;"><strong>' . number_format($row['total_qty']) . '</strong></td>
                <td style="text-align:right;">' . number_format($row['total_amount'], 2) . '</td>
            </tr>';
        }
        $counter++;
    }
}

$html .= '</tbody>
<tfoot>
<tr>';

if ($singleProduct) {
    $html .= '
    <td colspan="2" style="text-align:center;">TOTAL</td>
    <td style="text-align:center;">' . $grandOrders . '</td>
    <td style="text-align:center;">' . number_format($grandQty) . '</td>
    <td style="text-align:right;">' . number_format($grandAmount, 2) . '</td>';
} else {
    $html .= '
    <td colspan="3" style="text-align:center;">TOTAL</td>
    <td style="text-align:center;">' . $grandOrders . '</td>
    <td style="text-align:center;">' . number_format($grandQty) . '</td>
    <td style="text-align:right;">' . number_format($grandAmount, 2) . '</td>';
}

$html .= '
</tr>
</tfoot>
</table>

</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('ItemSaleByRep_' . $validfrom . '_to_' . $validto . '.pdf', ['Attachment' => 0]);
exit;
?>