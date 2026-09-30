<?php
require_once('../connection/db.php');

$validfrom  = $_POST['validfrom'];
$validto    = $_POST['validto'];
$productID  = isset($_POST['product']) ? intval($_POST['product']) : 0;
$repID      = isset($_POST['rep'])     ? intval($_POST['rep'])     : 0;
$areaID     = isset($_POST['area'])    ? intval($_POST['area'])    : 0;

if ($productID > 0) {
    $sql = "SELECT
                `ue`.`idtbl_employee`,
                `ue`.`name`                                      AS `repname`,
                `p`.`idtbl_product`,
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

    $sql .= " GROUP BY `ue`.`idtbl_employee`
              ORDER BY `total_qty` DESC";

    $result = $conn->query($sql);

    if (!$result || $result->num_rows == 0) {
        echo "<div style='color: red; font-size:20px;'>No Records Found</div>";
        exit;
    }

    /* Fetch product name for header */
    $firstRow    = null;
    $rows        = [];
    while ($row = $result->fetch_assoc()) {
        if ($firstRow === null) $firstRow = $row;
        $rows[] = $row;
    }
    $productName = htmlspecialchars($firstRow['product_name']);

    $html  = '<p class="small font-weight-bold text-dark mb-1">Product : ' . $productName . '</p>';
    $html .= '<table class="table table-striped table-bordered table-sm small w-100" id="reportTable" style="width:100%!important;">
        <thead>
            <tr>
                <th>#</th>
                <th>Rep Name</th>
                <th class="text-center">Orders</th>
                <th class="text-center">Qty Sold</th>
                <th class="text-center">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>';

    $grandQty    = 0;
    $grandAmount = 0;
    $grandOrders = 0;
    $counter     = 1;

    foreach ($rows as $row) {
        $grandQty    += $row['total_qty'];
        $grandAmount += $row['total_amount'];
        $grandOrders += $row['order_count'];

        $html .= '<tr>
            <td>' . $counter++ . '</td>
            <td>' . htmlspecialchars($row['repname']) . '</td>
            <td class="text-center">' . $row['order_count'] . '</td>
            <td class="text-center"><strong>' . number_format($row['total_qty']) . '</strong></td>
            <td class="text-center">' . number_format($row['total_amount'], 2) . '</td>
        </tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr class="table-dark">
                <td colspan="2" class="text-center"><strong>Total</strong></td>
                <td class="text-center"><strong>' . $grandOrders . '</strong></td>
                <td class="text-center"><strong>' . number_format($grandQty) . '</strong></td>
                <td class="text-center"><strong>' . number_format($grandAmount, 2) . '</strong></td>
            </tr>
        </tfoot>
    </table>';

    echo $html;
    exit;
}

$sql = "SELECT
            `ue`.`idtbl_employee`,
            `ue`.`name`                                      AS `repname`,
            `p`.`idtbl_product`,
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

$sql .= " GROUP BY `ue`.`idtbl_employee`, `p`.`idtbl_product`
          ORDER BY `total_qty` DESC";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    echo "<div style='color: red; font-size:20px;'>No Records Found</div>";
    exit;
}

$html  = '<table class="table w-100" id="reportTable" style="width:100%!important;">
    <thead>
        <tr>
            <th>#</th>
            <th>Rep Name</th>
            <th>Product</th>
            <th class="text-center">Orders</th>
            <th class="text-center">Qty Sold</th>
            <th class="text-center">Amount (Rs.)</th>
        </tr>
    </thead>
    <tbody>';

$grandQty    = 0;
$grandAmount = 0;
$grandOrders = 0;
$counter     = 1;

while ($row = $result->fetch_assoc()) {
    $grandQty    += $row['total_qty'];
    $grandAmount += $row['total_amount'];
    $grandOrders += $row['order_count'];

    $html .= '<tr>
        <td>' . $counter++ . '</td>
        <td>' . htmlspecialchars($row['repname']) . '</td>
        <td>' . htmlspecialchars($row['product_name']) . '</td>
        <td class="text-center">' . $row['order_count'] . '</td>
        <td class="text-center"><strong>' . number_format($row['total_qty']) . '</strong></td>
        <td class="text-center">' . number_format($row['total_amount'], 2) . '</td>
    </tr>';
}

$html .= '</tbody>
    <tfoot>
        <tr class="table-dark">
            <td colspan="3" class="text-center"><strong>Total</strong></td>
            <td class="text-center"><strong>' . $grandOrders . '</strong></td>
            <td class="text-center"><strong>' . number_format($grandQty) . '</strong></td>
            <td class="text-center"><strong>' . number_format($grandAmount, 2) . '</strong></td>
        </tr>
    </tfoot>
</table>';

echo $html;
