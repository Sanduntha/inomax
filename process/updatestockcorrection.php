<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['userid'])){echo json_encode(['status'=>'error','message'=>'Not logged in']);exit;}
require_once('../connection/db.php');

$userID = $_SESSION['userid'];
$idtbl_stock = $_POST['idtbl_stock'];
$newqty = $_POST['newqty'];

if ($idtbl_stock === '' || $newqty === '' || !is_numeric($newqty) || $newqty < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$checkSql = "SELECT `s`.`corrected`, `u`.`username` AS corrected_by_name
             FROM `tbl_stock` AS `s`
             LEFT JOIN `tbl_user` AS `u` ON `u`.`idtbl_user` = `s`.`corrected_user`
             WHERE `s`.`idtbl_stock`='$idtbl_stock'";
$checkResult = $conn->query($checkSql);

if ($checkResult->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Stock record not found']);
    exit;
}

$row = $checkResult->fetch_assoc();

if ($row['corrected'] == 1 && $userID != 1) {
    echo json_encode([
        'status'    => 'locked',
        'locked_by' => $row['corrected_by_name'] ? $row['corrected_by_name'] : 'another user'
    ]);
    exit;
}

$correcteddatetime = date('Y-m-d H:i:s');
$newqtyEscaped = $conn->real_escape_string($newqty);

$updateSql = "UPDATE `tbl_stock`
              SET `qty`='$newqtyEscaped',
                  `corrected`=1,
                  `corrected_user`='$userID',
                  `corrected_datetime`='$correcteddatetime'
              WHERE `idtbl_stock`='$idtbl_stock'";

if ($conn->query($updateSql) === true) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>