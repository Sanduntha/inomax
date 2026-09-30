<?php
session_start();
require_once('../connection/db.php');

$fromdate = $_POST['fromdate'];
$todate = $_POST['todate'];

$sqlInv = "
SELECT  
    co.idtbl_customer_order,
    co.cuspono,
    co.date,
    COALESCE(inv.idtbl_invoice, 0) AS idtbl_invoice,
    COALESCE(inv.invoiceno, '-') AS invoiceno,
    co.nettotal AS co_nettotal,
    COALESCE(SUM(d.unitprice * d.qty), 0) AS total_cost,
    COALESCE(SUM(d.discount), 0) AS total_item_discount,
    COALESCE(inv.discount, 0) AS invoice_discount
FROM tbl_customer_order AS co
LEFT JOIN tbl_invoice AS inv 
    ON inv.tbl_customer_order_idtbl_customer_order = co.idtbl_customer_order
    AND inv.status IN (1, 2)
LEFT JOIN tbl_invoice_detail AS d 
    ON d.tbl_invoice_idtbl_invoice = inv.idtbl_invoice
    AND d.status = '1'
WHERE co.status = '1'
    AND co.confirm = '1'
    AND co.date BETWEEN '$fromdate' AND '$todate'
GROUP BY co.idtbl_customer_order
ORDER BY co.date ASC, inv.invoiceno ASC
";

$invResult = $conn->query($sqlInv);
$invoiceMap = [];
$invoiceOrder = [];
$grandTotalCheck = 0;

while ($row = $invResult->fetch_assoc()) {
    $total_profit = $row['co_nettotal'] - $row['total_cost'];
    $profit_with_disc = $total_profit - $row['invoice_discount'] - $row['total_item_discount'];
    $row['invoice_net_profit'] = $profit_with_disc;
    $grandTotalCheck += $profit_with_disc;
    $invoiceMap[$row['idtbl_customer_order']] = $row;
    $invoiceOrder[] = $row['idtbl_customer_order'];
}

$coIds = implode(',', array_map('intval', $invoiceOrder));
$linesByOrder = [];

if (!empty($coIds)) {
    $sqlLines = "
    SELECT
        co.idtbl_customer_order,
        p.product_name,
        d.idtbl_invoice_detail,
        d.qty,
        d.saleprice,
        d.unitprice,
        COALESCE(d.discount, 0) AS discount,
        ((d.saleprice - d.unitprice) * d.qty) - COALESCE(d.discount, 0) AS line_net_profit
    FROM tbl_customer_order AS co
    JOIN tbl_invoice AS inv
        ON inv.tbl_customer_order_idtbl_customer_order = co.idtbl_customer_order
        AND inv.status IN (1, 2)
    JOIN tbl_invoice_detail AS d
        ON d.tbl_invoice_idtbl_invoice = inv.idtbl_invoice
        AND d.status = '1'
    JOIN tbl_product AS p
        ON p.idtbl_product = d.tbl_product_idtbl_product
    WHERE co.idtbl_customer_order IN ($coIds)
    ORDER BY co.date ASC, inv.invoiceno ASC, d.idtbl_invoice_detail ASC
    ";

    $lineResult = $conn->query($sqlLines);
    while ($r = $lineResult->fetch_assoc()) {
        $linesByOrder[$r['idtbl_customer_order']][] = $r;
    }
}

function distributeExact($lines, $target) {
    $total = array_sum(array_column($lines, 'line_net_profit'));
    $shares = [];
    $allocated = 0;
    $remainders = [];

    foreach ($lines as $i => $line) {
        if ($total == 0) {
            $raw = $target / count($lines);
        } else {
            $raw = ($line['line_net_profit'] / $total) * $target;
        }
        $floored = floor($raw * 100) / 100;
        $shares[$i] = $floored;
        $allocated += $floored;
        $remainders[$i] = $raw - $floored;
    }

    $remaining = (int)round(($target - $allocated) * 100);
    arsort($remainders);
    foreach ($remainders as $i => $r) {
        if ($remaining <= 0) break;
        $shares[$i] += 0.01;
        $remaining--;
    }

    return $shares;
}

echo '<table class="table table-bordered table-striped table-sm nowrap" id="dataTable">
        <thead>
            <tr>
                <th>#</th>
                <th class="text-center">PO No</th>
                <th class="text-center">Invoice No</th>
                <th class="text-center">Product</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Profit</th>
            </tr>
        </thead>
        <tbody>';

$c = 0;
$full_profit = 0;

foreach ($invoiceOrder as $coId) {
    $inv = $invoiceMap[$coId];
    $targetProfit = $inv['invoice_net_profit'];

    if (!isset($linesByOrder[$coId])) {
        $c++;
        $full_profit += $targetProfit;
        echo '<tr>
                <td class="text-center">' . $c . '</td>
                <td class="text-center">' . $inv['cuspono'] . '</td>
                <td class="text-center">' . $inv['invoiceno'] . '</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-right">' . number_format($targetProfit, 2, '.', ',') . '</td>
            </tr>';
        continue;
    }

    $lines = $linesByOrder[$coId];
    $distributed = distributeExact($lines, $targetProfit);

    foreach ($lines as $i => $line) {
        $c++;
        $lineProfit = $distributed[$i];
        $full_profit = round($full_profit + $lineProfit, 2);

        echo '<tr>
                <td class="text-center">' . $c . '</td>
                <td class="text-center">' . $inv['cuspono'] . '</td>
                <td class="text-center">' . $inv['invoiceno'] . '</td>
                <td class="text-center">' . $line['product_name'] . '</td>
                <td class="text-center">' . $line['qty'] . '</td>
                <td class="text-right">' . number_format($lineProfit, 2, '.', ',') . '</td>
            </tr>';
    }
}

echo '</tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-center"><strong>Total</strong></td>
                <td class="text-right"><strong>' . number_format($full_profit, 2) . '</strong></td>
            </tr>
        </tfoot>
    </table>';
?>