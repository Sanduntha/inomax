<?php
session_start();
if(!isset($_SESSION['userid'])){header("Location:../index.php");exit;}
require_once('../connection/db.php');

$productId = $_POST['productId'];

// NOTE: adjust `u`.`username` below to match your actual tbl_user column name if different.
$sql = "SELECT `s`.`idtbl_stock`, `s`.`batchno`, `s`.`batchqty`, `s`.`qty`,
               `s`.`corrected`, `s`.`corrected_datetime`, `u`.`username` AS corrected_by_name
        FROM `tbl_stock` AS `s`
        LEFT JOIN `tbl_user` AS `u` ON `u`.`idtbl_user` = `s`.`corrected_user`
        WHERE `s`.`status`=1 AND `s`.`tbl_product_idtbl_product`='$productId'
        ORDER BY `s`.`batchno` ASC";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo '<tr><td colspan="8" class="text-center">No stock batches found for this product.</td></tr>';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $isCorrected = ($row['corrected'] == 1);
    $isLocked = ($isCorrected && $_SESSION['userid'] != 79);

    $correctedBadge = $isCorrected
        ? '<span class="badge badge-warning">Yes</span>'
        : '<span class="badge badge-light">No</span>';

    echo '<tr data-stockid="' . $row['idtbl_stock'] . '" data-batchno="' . htmlspecialchars($row['batchno']) . '">';
    echo '<td>' . htmlspecialchars($row['batchno']) . '</td>';
    echo '<td>' . $row['batchqty'] . '</td>';
    echo '<td class="text-center">' . $row['qty'] . '</td>';
    echo '<td>';
    if ($isLocked) {
        echo '<input type="number" class="form-control form-control-sm newQtyInput" value="' . $row['qty'] . '" disabled>';
    } else {
        echo '<input type="number" min="0" class="form-control form-control-sm newQtyInput" value="' . $row['qty'] . '">';
    }
    echo '</td>';
    echo '<td class="text-center">' . $correctedBadge . '</td>';
    echo '<td>' . ($row['corrected_by_name'] ? htmlspecialchars($row['corrected_by_name']) : '-') . '</td>';
    echo '<td>' . ($row['corrected_datetime'] ? $row['corrected_datetime'] : '-') . '</td>';
    echo '<td class="text-center">';
    if ($isLocked) {
        echo '<button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Already corrected — admin only"><i class="fas fa-lock"></i></button>';
    } else {
        echo '<button type="button" class="btn btn-sm btn-outline-dark updateStockBtn"><i class="fas fa-save"></i>&nbsp;Update</button>';
    }
    echo '</td>';
    echo '</tr>';
}
?>