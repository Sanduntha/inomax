<?php 
require_once('../connection/db.php');

$validfrom       = $_POST['validfrom'];
$validto         = $_POST['validto'];
$customerID      = isset($_POST['customer'])        ? $_POST['customer']        : 0;
$repID           = isset($_POST['rep'])             ? $_POST['rep']             : 0;
$areaID          = isset($_POST['area'])            ? $_POST['area']            : 0;
$groupByRep      = isset($_POST['groupByRep'])      ? intval($_POST['groupByRep'])      : 0;
$groupByCustomer = isset($_POST['groupByCustomer']) ? intval($_POST['groupByCustomer']) : 0;

/* MODE A — Group by Rep*/
if ($groupByRep == 1) {

    $sql = "SELECT
                `ue`.`idtbl_employee`,
                `ue`.`name`                               AS `repname`,
                COUNT(`u`.`idtbl_customer_order`)         AS `order_count`,
                SUM(`u`.`nettotal`)                       AS `subtotal`
            FROM `tbl_customer_order` AS `u`
            LEFT JOIN `tbl_customer`  AS `uf` ON `u`.`tbl_customer_idtbl_customer`  = `uf`.`idtbl_customer`
            LEFT JOIN `tbl_employee`  AS `ue` ON `u`.`tbl_employee_idtbl_employee`  = `ue`.`idtbl_employee`
            LEFT JOIN `tbl_area`      AS `ub` ON `u`.`tbl_area_idtbl_area`          = `ub`.`idtbl_area`
            WHERE `u`.`date`    BETWEEN '$validfrom' AND '$validto'
            AND   `u`.`status`  = 1";

    if ($customerID > 0) $sql .= " AND `u`.`tbl_customer_idtbl_customer` = '$customerID'";
    if ($repID > 0)      $sql .= " AND `u`.`tbl_employee_idtbl_employee` = '$repID'";
    if ($areaID > 0)     $sql .= " AND `u`.`tbl_area_idtbl_area` = '$areaID'";

    $sql .= " GROUP BY `ue`.`idtbl_employee` ORDER BY `ue`.`name` ASC";

    $result = $conn->query($sql);

    if (!$result || $result->num_rows == 0) {
        echo "<div style='color: red; font-size:20px;'>No Records</div>";
        exit;
    }

    $html = '<table class="table table-striped table-bordered table-sm small" id="reportTable">
        <thead>
            <tr>
                <th>Rep</th>
                <th class="text-center">Total Orders</th>
                <th class="text-center">Amount</th>
            </tr>
        </thead>
        <tbody>';

    $grandTotal  = 0;
    $grandOrders = 0;

    while ($row = $result->fetch_assoc()) {
        $grandTotal  += $row['subtotal'];
        $grandOrders += $row['order_count'];
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['repname']) . '</td>
                    <td class="text-center">' . $row['order_count'] . '</td>
                    <td class="text-center">' . number_format($row['subtotal'], 2) . '</td>
                  </tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr>
                <td class="text-center"><strong>Total</strong></td>
                <td class="text-center"><strong>' . $grandOrders . '</strong></td>
                <td class="text-center"><strong>' . number_format($grandTotal, 2) . '</strong></td>
            </tr>
        </tfoot>
    </table>';

    echo $html;
    exit;
}

/*MODE B — Group by Customer*/
if ($groupByCustomer == 1) {

    $sql = "SELECT
                `uf`.`idtbl_customer`,
                `uf`.`customer`                           AS `cusname`,
                COUNT(`u`.`idtbl_customer_order`)         AS `order_count`,
                SUM(`u`.`nettotal`)                       AS `subtotal`
            FROM `tbl_customer_order` AS `u`
            LEFT JOIN `tbl_customer`  AS `uf` ON `u`.`tbl_customer_idtbl_customer`  = `uf`.`idtbl_customer`
            LEFT JOIN `tbl_employee`  AS `ue` ON `u`.`tbl_employee_idtbl_employee`  = `ue`.`idtbl_employee`
            LEFT JOIN `tbl_area`      AS `ub` ON `u`.`tbl_area_idtbl_area`          = `ub`.`idtbl_area`
            WHERE `u`.`date`    BETWEEN '$validfrom' AND '$validto'
            AND   `u`.`status`  = 1";

    if ($customerID > 0) $sql .= " AND `u`.`tbl_customer_idtbl_customer` = '$customerID'";
    if ($repID > 0)      $sql .= " AND `u`.`tbl_employee_idtbl_employee` = '$repID'";
    if ($areaID > 0)     $sql .= " AND `u`.`tbl_area_idtbl_area` = '$areaID'";

    $sql .= " GROUP BY `uf`.`idtbl_customer` ORDER BY `uf`.`customer` ASC";

    $result = $conn->query($sql);

    if (!$result || $result->num_rows == 0) {
        echo "<div style='color: red; font-size:20px;'>No Records</div>";
        exit;
    }

    $html = '<table class="table table-striped table-bordered table-sm small" id="reportTable">
        <thead>
            <tr>
                <th>Customer</th>
                <th class="text-center">Total Orders</th>
                <th class="text-center">Amount</th>
            </tr>
        </thead>
        <tbody>';

    $grandTotal  = 0;
    $grandOrders = 0;

    while ($row = $result->fetch_assoc()) {
        $grandTotal  += $row['subtotal'];
        $grandOrders += $row['order_count'];
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['cusname']) . '</td>
                    <td class="text-center">' . $row['order_count'] . '</td>
                    <td class="text-center">' . number_format($row['subtotal'], 2) . '</td>
                  </tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr>
                <td class="text-center"><strong>Total</strong></td>
                <td class="text-center"><strong>' . $grandOrders . '</strong></td>
                <td class="text-center"><strong>' . number_format($grandTotal, 2) . '</strong></td>
            </tr>
        </tfoot>
    </table>';

    echo $html;
    exit;
}

/*MODE C — Normal report (no grouping) — original behaviour*/

$sql = "SELECT `u`.`idtbl_customer_order`, `u`.`cuspono`, `u`.`nettotal`,
               `ub`.`area`, `uf`.`customer` AS `cusname`, `ue`.`name` AS `repname`
        FROM `tbl_customer_order` AS `u`
        LEFT JOIN `tbl_customer`  AS `uf` ON `u`.`tbl_customer_idtbl_customer`  = `uf`.`idtbl_customer`
        LEFT JOIN `tbl_employee`  AS `ue` ON `u`.`tbl_employee_idtbl_employee`  = `ue`.`idtbl_employee`
        LEFT JOIN `tbl_area`      AS `ub` ON `u`.`tbl_area_idtbl_area`          = `ub`.`idtbl_area`
        WHERE `u`.`date` BETWEEN '$validfrom' AND '$validto'
        AND   `u`.`status`  = 1";

if ($customerID > 0) $sql .= " AND `u`.`tbl_customer_idtbl_customer` = '$customerID'";
if ($repID > 0)      $sql .= " AND `u`.`tbl_employee_idtbl_employee` = '$repID'";
if ($areaID > 0)     $sql .= " AND `u`.`tbl_area_idtbl_area` = '$areaID'";

$sql .= " GROUP BY `u`.`idtbl_customer_order`";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    echo "<div style='color: red; font-size:20px;'>No Records</div>";
    exit;
}

$html = '<table class="table table-striped table-bordered table-sm small" id="reportTable">
    <thead>
        <tr>
            <th>Invoice</th>
            <th class="text-center">Customer</th>
            <th class="text-center">Rep</th>
            <th class="text-center">Area</th>
            <th class="text-center">Amount</th>
        </tr>
    </thead>
    <tbody>';

$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $row['cuspono'] . '</td>
        <td class="text-center">' . $row['cusname'] . '</td>
        <td class="text-center">' . $row['repname'] . '</td>
        <td class="text-center">' . $row['area'] . '</td>
        <td class="text-center">' . number_format($row['nettotal'], 2) . '</td>
    </tr>';
    $totalAmount += $row['nettotal'];
}

$html .= '</tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-center"><strong>Total</strong></td>
            <td class="text-center"><strong>' . number_format($totalAmount, 2) . '</strong></td>
        </tr>
    </tfoot>
</table>';

echo $html;
?>