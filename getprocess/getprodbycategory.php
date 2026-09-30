<?php
session_start();
if(!isset($_SESSION['userid'])){header("Location:../index.php");exit;}
require_once('../connection/db.php');

$categoryId = isset($_POST['categoryId']) ? trim($_POST['categoryId']) : '';
$searchTerm = isset($_POST['q']) ? trim($_POST['q']) : '';
$page       = isset($_POST['page']) && (int)$_POST['page'] > 0 ? (int)$_POST['page'] : 1;

$pageSize = 20;
$offset   = ($page - 1) * $pageSize;

$where = "WHERE `status`=1";

if ($categoryId !== '') {
    $categoryIdEscaped = $conn->real_escape_string($categoryId);
    $where .= " AND `tbl_product_category_idtbl_product_category`='$categoryIdEscaped'";
}

if ($searchTerm !== '') {
    $searchEscaped = $conn->real_escape_string($searchTerm);
    $where .= " AND (`product_name` LIKE '%$searchEscaped%' OR `product_code` LIKE '%$searchEscaped%')";
}

// Fetch one extra row to know if there's a next page, without a second COUNT query.
$limit = $pageSize + 1;

$sql = "SELECT `idtbl_product`, `product_name`, `product_code` FROM `tbl_product`
        $where
        ORDER BY `product_name` ASC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$results = array();
$count = 0;

while ($row = $result->fetch_assoc()) {
    $count++;
    if ($count > $pageSize) {
        break; // this is the lookahead row, don't include it
    }
    $obj = new stdClass();
    $obj->id   = $row['idtbl_product'];
    $obj->text = $row['product_code'] . ' - ' . $row['product_name'];
    array_push($results, $obj);
}

$response = array(
    'results'    => $results,
    'pagination' => array('more' => $count > $pageSize)
);

echo json_encode($response);
?>