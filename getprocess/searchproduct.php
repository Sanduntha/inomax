<?php
require_once('../connection/db.php');

header('Content-Type: application/json');

$search = isset($_GET['q'])    ? trim($_GET['q'])       : '';
$page   = isset($_GET['page']) ? intval($_GET['page'])  : 1;
$limit  = 20;
$offset = ($page - 1) * $limit;

$search = $conn->real_escape_string($search);

$countSql = "SELECT COUNT(*) AS total FROM `tbl_product`
             WHERE `status` = 1
             " . ($search !== '' ? "AND `product_name` LIKE '%$search%'" : "") . "";
$countResult = $conn->query($countSql);
$countRow    = $countResult->fetch_assoc();
$total       = (int)$countRow['total'];

$sql = "SELECT `idtbl_product`, `product_name`
        FROM `tbl_product`
        WHERE `status` = 1
        " . ($search !== '' ? "AND `product_name` LIKE '%$search%'" : "") . "
        ORDER BY `product_name` ASC
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$items = [];

// if ($page === 1 && $search === '') {
//     $items[] = ['id' => '0', 'text' => '-- All Products --'];
// }

while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id'   => $row['idtbl_product'],
        'text' => $row['product_name']
    ];
}

echo json_encode([
    'results'    => $items,
    'pagination' => [
        'more' => ($offset + $limit) < $total
    ]
]);
