<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once('../connection/db.php');

if (!$conn) {
    echo json_encode([
        'results'    => [],
        'pagination' => ['more' => false]
    ]);
    exit;
}

$search  = isset($_GET['q'])    ? trim($_GET['q'])     : '';
$page    = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Count total
if (!empty($search)) {
    $searchParam = '%' . $conn->real_escape_string($search) . '%';
    $countSql    = "SELECT COUNT(*) AS total FROM `tbl_customer` WHERE `status` = 1 AND `customer` LIKE '$searchParam'";
} else {
    $countSql = "SELECT COUNT(*) AS total FROM `tbl_customer` WHERE `status` = 1";
}
$countResult = $conn->query($countSql);
$totalCount  = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

// Fetch rows
if (!empty($search)) {
    $dataSql = "SELECT `idtbl_customer`, `customer`
                FROM `tbl_customer`
                WHERE `status` = 1
                AND `customer` LIKE '$searchParam'
                ORDER BY `customer`
                LIMIT $perPage OFFSET $offset";
} else {
    $dataSql = "SELECT `idtbl_customer`, `customer`
                FROM `tbl_customer`
                WHERE `status` = 1
                ORDER BY `customer`
                LIMIT $perPage OFFSET $offset";
}
$dataResult = $conn->query($dataSql);

$customers = [];

// Prepend "All Customers" only on first page with no search term
if ($page === 1 && empty($search)) {
    $customers[] = [
        'id'   => 'all',
        'text' => '-- All Customers --'
    ];
}

if ($dataResult) {
    while ($row = $dataResult->fetch_assoc()) {
        $customers[] = [
            'id'   => $row['idtbl_customer'],
            'text' => $row['customer']
        ];
    }
}

echo json_encode([
    'results'    => $customers,
    'pagination' => ['more' => ($offset + $perPage) < $totalCount]
]);
exit;
