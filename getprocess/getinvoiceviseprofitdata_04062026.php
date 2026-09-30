<?php
session_start();
require_once('../connection/db.php');
$fromdate = $_POST['fromdate'];
$todate = $_POST['todate'];

$sql = "
SELECT  
    co.idtbl_customer_order,
    co.date,
    co.cuspono,
    inv.invoiceno,
    co.nettotal AS total_sale, 
    COALESCE(SUM(d.unitprice * d.qty), 0) AS total_cost,
    (co.nettotal - COALESCE(SUM(d.unitprice * d.qty), 0)) AS total_profit,
    COALESCE(SUM(d.discount), 0) AS total_discount,
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
";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped table-sm nowrap" id="dataTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">PO No</th>
                    <th class="text-center">Invoice No</th>
                    <th class="text-center">Total Sale</th>
                    <th class="text-center">Total Cost</th>
                    <th class="text-center">Total Profit</th>
                    <th class="text-center">Profit With Discounts</th>
                </tr>
            </thead>
            <tbody>';
    $c = 0;
    $sum_sale = 0;
    $sum_cost = 0;
    $sum_profit = 0;
    $total_profit = 0;
    while ($row = $result->fetch_assoc()) {
        $c++;
        $sum_sale        += $row['total_sale'];
        $sum_cost        += $row['total_cost'];
        $sum_profit      += $row['total_profit'];
        $profit_with_disc = $row['total_profit'] - $row['invoice_discount'] - $row['total_discount'];
        $total_profit    += $profit_with_disc;

        echo '<tr>
                <td class="text-center">' . $c . '</td>
                <td class="text-center">' . $row['date'] . '</td>
                <td class="text-center">' . $row['cuspono'] . '</td>
                <td class="text-center">' . ($row['invoiceno'] ?? '-') . '</td>
                <td class="text-right">' . number_format($row['total_sale'], 2, '.', ',') . '</td>
                <td class="text-right">' . number_format($row['total_cost'], 2, '.', ',') . '</td>
                <td class="text-right">' . number_format($row['total_profit'], 2, '.', ',') . '</td>
                <td class="text-right">' . number_format($profit_with_disc, 2, '.', ',') . '</td>
            </tr>';
    }
    echo '</tbody>
          <tfoot>
              <tr>
                  <td colspan="4" class="text-center"><strong>Total</strong></td>
                  <td class="text-right"><strong>' . number_format($sum_sale, 2) . '</strong></td>
                  <td class="text-right"><strong>' . number_format($sum_cost, 2) . '</strong></td>
                  <td class="text-right"><strong>' . number_format($sum_profit, 2) . '</strong></td>
                  <td class="text-right"><strong>' . number_format($total_profit, 2) . '</strong></td>
              </tr>
          </tfoot>
      </table>';
} else {
    echo '<div class="alert alert-info" role="alert">No records found.</div>';
}
?>