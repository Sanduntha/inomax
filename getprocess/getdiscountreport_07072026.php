<?php
require_once('../connection/db.php');

$customerRaw = $_POST['customer'] ?? '0';
$validfrom   = $_POST['validfrom'] ?? '';
$validto     = $_POST['validto']   ?? '';

$isAll      = ($customerRaw === 'all' || $customerRaw === '0' || intval($customerRaw) <= 0);
$customerID = $isAll ? null : intval($customerRaw);

$validfrom = $conn->real_escape_string($validfrom);
$validto   = $conn->real_escape_string($validto);

$customerFilter = $isAll ? "" : "AND co.tbl_customer_idtbl_customer = " . intval($customerID);

$sql = "
SELECT
    u.idtbl_invoice,
    u.invoiceno,
    u.date,
    u.total              AS invoice_gross,
    u.nettotal,
    COALESCE(co.podiscount, 0)           AS po_discount,
    COALESCE(co.podiscountpercentage, 0) AS po_discount_pct,
    uc.customer                          AS cusname,
    ue.name                              AS repname
FROM tbl_invoice u
INNER JOIN tbl_customer_order co
    ON u.tbl_customer_order_idtbl_customer_order = co.idtbl_customer_order
LEFT JOIN tbl_customer uc
    ON co.tbl_customer_idtbl_customer = uc.idtbl_customer
LEFT JOIN tbl_employee ue
    ON co.tbl_employee_idtbl_employee = ue.idtbl_employee
WHERE u.status = 1
$customerFilter
";

if (!empty($validfrom) && !empty($validto)) {
    $sql .= " AND u.date BETWEEN '$validfrom' AND '$validto'";
}

$sql .= " ORDER BY u.date ASC, u.idtbl_invoice ASC";

$result = $conn->query($sql);

if (!$result) {
    echo "<div style='color:red;font-size:16px;'>Query error: " . htmlspecialchars($conn->error) . "</div>";
    return;
}
if ($result->num_rows == 0) {
    echo "<div style='color:red;font-size:16px;'>No Records Found.</div>";
    return;
}

$invoices   = [];
$invoiceIds = [];
while ($row = $result->fetch_assoc()) {
    $invoices[$row['idtbl_invoice']] = $row;
    $invoiceIds[] = intval($row['idtbl_invoice']);
}

$itemsByInvoice = [];
if (!empty($invoiceIds)) {
    $idList  = implode(',', $invoiceIds);

    $itemSql = "
    SELECT
        d.tbl_invoice_idtbl_invoice           AS invoice_id,
        p.product_code                        AS itemcode,
        p.product_name                        AS product_name,
        d.qty,
        d.unitprice,
        d.saleprice,
        d.total                               AS item_total,
        ((d.saleprice * d.qty) - d.total)     AS item_discount
    FROM tbl_invoice_detail d
    LEFT JOIN tbl_product p ON d.tbl_product_idtbl_product = p.idtbl_product
    WHERE d.tbl_invoice_idtbl_invoice IN ($idList)
    AND d.status = 1
    ORDER BY d.tbl_invoice_idtbl_invoice, p.product_name ASC
    ";

    $itemResult = $conn->query($itemSql);
    if (!$itemResult) {
        echo "<div style='color:red;font-size:16px;'>Item query error: " . htmlspecialchars($conn->error) . "</div>";
        return;
    }
    while ($row = $itemResult->fetch_assoc()) {
        $itemsByInvoice[$row['invoice_id']][] = $row;
    }
}

$totalGross      = 0;
$totalPODiscount = 0;
$totalItemDisc   = 0;
$totalNet        = 0;
$rows            = [];

foreach ($invoices as $id => $inv) {
    $items       = $itemsByInvoice[$id] ?? [];
    $itemDiscSum = 0;
    foreach ($items as $item) {
        $d = floatval($item['item_discount']);
        if ($d > 0) $itemDiscSum += $d;
    }

    $gross   = floatval($inv['invoice_gross']);
    $poDisc  = floatval($inv['po_discount']);
    $net     = floatval($inv['nettotal']);

    $totalGross      += $gross;
    $totalPODiscount += $poDisc;
    $totalItemDisc   += $itemDiscSum;
    $totalNet        += $net;

    $rows[] = [
        'inv'         => $inv,
        'items'       => $items,
        'itemDiscSum' => $itemDiscSum,
    ];
}

$totalDiscount = $totalPODiscount + $totalItemDisc;

echo '<script id="summaryData">' . json_encode([
    'gross'          => $totalGross,
    'po_discount'    => $totalPODiscount,
    'item_discount'  => $totalItemDisc,
    'total_discount' => $totalDiscount,
    'net'            => $totalNet,
]) . '</script>';

$html  = '<table class="table table-striped table-bordered table-sm small" id="discountReportTable" style="width:100%">';
$html .= '<thead><tr>';
$html .= '<th style="width:30px;">#</th>';
$html .= '<th>Invoice No</th>';
$html .= '<th>Date</th>';
$html .= '<th>Customer</th>';
$html .= '<th>Rep</th>';
$html .= '<th class="text-right">Gross Total</th>';
$html .= '<th class="text-right">PO Discount</th>';
$html .= '<th class="text-center">PO Disc %</th>';
$html .= '<th class="text-right">Item Discount</th>';
$html .= '<th class="text-right">Total Discount</th>';
$html .= '<th class="text-right">Net Total</th>';
$html .= '</tr></thead><tbody>';

$x = 0;

foreach ($rows as $entry) {
    $inv         = $entry['inv'];
    $itemDiscSum = $entry['itemDiscSum'];
    $id          = $inv['idtbl_invoice'];

    $gross     = floatval($inv['invoice_gross']);
    $poDisc    = floatval($inv['po_discount']);
    $poPct     = floatval($inv['po_discount_pct']);
    $totalDisc = $poDisc + $itemDiscSum;
    $net       = floatval($inv['nettotal']);

    $html .= '<tr data-invoice-id="' . $id . '">';
    $html .= '<td>' . ($x+1) . '</td>';
    $html .= '<td>' . htmlspecialchars($inv['invoiceno']) . '</td>';
    $html .= '<td>' . htmlspecialchars($inv['date']) . '</td>';
    $html .= '<td>' . htmlspecialchars($inv['cusname'] ?? '—') . '</td>';
    $html .= '<td>' . htmlspecialchars($inv['repname'] ?? '—') . '</td>';
    $html .= '<td class="text-right">' . number_format($gross, 2) . '</td>';
    $html .= '<td class="text-right">' . ($poDisc > 0 ? number_format($poDisc, 2) : '—') . '</td>';
    $html .= '<td class="text-center">' . ($poPct > 0 ? $poPct . '%' : '—') . '</td>';
    $html .= '<td class="text-right">' . ($itemDiscSum > 0 ? number_format($itemDiscSum, 2) : '—') . '</td>';
    $html .= '<td class="text-right">' . number_format($totalDisc, 2) . '</td>';
    $html .= '<td class="text-right">' . number_format($net, 2) . '</td>';
    $html .= '</tr>';
    $x++;
}

$html .= '</tbody>';
$html .= '<tfoot><tr>';
$html .= '<td colspan="5" class="text-center"><strong>Total</strong></td>';
$html .= '<td class="text-right"><strong>' . number_format($totalGross, 2) . '</strong></td>';
$html .= '<td class="text-right"><strong>' . number_format($totalPODiscount, 2) . '</strong></td>';
$html .= '<td class="text-center"></td>';
$html .= '<td class="text-right"><strong>' . number_format($totalItemDisc, 2) . '</strong></td>';
$html .= '<td class="text-right"><strong>' . number_format($totalDiscount, 2) . '</strong></td>';
$html .= '<td class="text-right"><strong>' . number_format($totalNet, 2) . '</strong></td>';
$html .= '</tr></tfoot></table>';

$detailData = [];
foreach ($rows as $entry) {
    $id    = $entry['inv']['idtbl_invoice'];
    $items = [];
    foreach ($entry['items'] as $item) {
        $itemDisc = floatval($item['item_discount']);
        $items[]  = [
            'itemcode'     => $item['itemcode']     ?? '—',
            'product_name' => $item['product_name'] ?? '—',
            'qty'          => intval($item['qty']),
            'unitprice'    => number_format($item['unitprice'], 2),
            'saleprice'    => number_format($item['saleprice'], 2),
            'item_disc'    => $itemDisc > 0 ? number_format($itemDisc, 2) : '—',
            'item_total'   => number_format($item['item_total'], 2),
        ];
    }
    $detailData[$id] = $items;
}

$html .= '<script id="invoiceDetailData">' . json_encode($detailData) . '</script>';
echo $html;