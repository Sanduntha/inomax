<?php
require_once('../connection/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerlist  = $_POST['customerlist']  ?? [];
    $invoicenumbers = trim($_POST['invoicenumbers'] ?? '');

    $hasCustomers = !empty($customerlist) && is_array($customerlist);
    $hasInvoices  = $invoicenumbers !== '';
    $isAllCustomers = $hasCustomers && in_array('all', $customerlist);

    if (!$hasCustomers && !$hasInvoices) {
        echo '<div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Please select at least one customer or enter invoice number(s).
              </div>';
        exit;
    }

    // Parse invoice numbers: split by comma or space, trim, remove empties
    $invoiceList = [];
    if ($hasInvoices) {
        $invoiceList = array_filter(array_map('trim', preg_split('/[\s,]+/', $invoicenumbers)));
    }

    // Build WHERE conditions
    $conditions   = ["co.status = 1", "co.delivered = 1"];
    $types        = '';
    $params       = [];

    if ($hasInvoices && !empty($invoiceList)) {
        $ph = implode(',', array_fill(0, count($invoiceList), '?'));
        $conditions[] = "i.invoiceno IN ($ph)";
        $types  .= str_repeat('s', count($invoiceList));
        $params  = array_merge($params, array_values($invoiceList));
    } elseif ($hasCustomers && !$isAllCustomers) {
        $ph = implode(',', array_fill(0, count($customerlist), '?'));
        $conditions[] = "co.tbl_customer_idtbl_customer IN ($ph)";
        $types  .= str_repeat('i', count($customerlist));
        $params  = array_merge($params, array_values($customerlist));
    }
    // If $isAllCustomers → no extra condition needed

    $whereClause = implode(' AND ', $conditions);

    $query = "SELECT 
                co.idtbl_customer_order,
                co.cuspono,
                c.customer,
                co.date AS order_date,
                co.total,
                co.discount,
                co.podiscount,
                co.nettotal,
                co.vat,
                co.delivereddatetime,
                i.invoiceno
              FROM tbl_customer_order co
              INNER JOIN tbl_customer c ON co.tbl_customer_idtbl_customer = c.idtbl_customer
              INNER JOIN tbl_invoice i ON co.idtbl_customer_order = i.tbl_customer_order_idtbl_customer_order
              WHERE $whereClause
              ORDER BY i.invoiceno ASC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo '<div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> Database error: ' . $conn->error . '
              </div>';
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $count          = 1;
        $totalGross     = 0;
        $totalDiscount  = 0;
        $totalPODiscount = 0;
        $totalVat       = 0;
        $totalNet       = 0;

        echo '<table id="dataTable" class="display table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-center">Invoice No</th>
                        <th class="text-center">PO Number</th>
                        <th class="text-center">Customer</th>
                        <th class="text-center">Order Date</th>
                        <th class="text-center">Delivery Date</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">PO Discount</th>
                        <th class="text-right">VAT</th>
                        <th class="text-right">Net Total</th>
                    </tr>
                </thead>
                <tbody>';

        while ($row = $result->fetch_assoc()) {
            $totalGross      += $row['total'];
            $totalDiscount   += $row['discount'];
            $totalPODiscount += $row['podiscount'];
            $totalVat        += $row['vat'];
            $totalNet        += $row['nettotal'];

            echo '<tr>
                    <td class="text-center">' . $count++ . '</td>
                    <td class="text-center"><strong>' . htmlspecialchars($row['invoiceno']) . '</strong></td>
                    <td class="text-center">' . htmlspecialchars($row['cuspono']) . '</td>
                    <td>' . htmlspecialchars($row['customer']) . '</td>
                    <td class="text-center">' . date('Y-m-d', strtotime($row['order_date'])) . '</td>
                    <td class="text-center">' . ($row['delivereddatetime'] ? date('Y-m-d', strtotime($row['delivereddatetime'])) : 'N/A') . '</td>
                    <td class="text-right">' . number_format($row['total'], 2) . '</td>
                    <td class="text-right">' . number_format($row['discount'], 2) . '</td>
                    <td class="text-right">' . number_format($row['podiscount'], 2) . '</td>
                    <td class="text-right">' . number_format($row['vat'], 2) . '</td>
                    <td class="text-right"><strong>' . number_format($row['nettotal'], 2) . '</strong></td>
                  </tr>';
        }

        echo '</tbody>
              <tfoot>
                <tr>
                    <th colspan="6" class="text-right">Total:</th>
                    <th class="text-right"><strong>' . number_format($totalGross, 2) . '</strong></th>
                    <th class="text-right"><strong>' . number_format($totalDiscount, 2) . '</strong></th>
                    <th class="text-right"><strong>' . number_format($totalPODiscount, 2) . '</strong></th>
                    <th class="text-right"><strong>' . number_format($totalVat, 2) . '</strong></th>
                    <th class="text-right"><strong>' . number_format($totalNet, 2) . '</strong></th>
                </tr>
              </tfoot>
            </table>';
    } else {
        echo '<div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i> No delivered orders found for the selected criteria.
              </div>';
    }

    $stmt->close();
    $conn->close();
}
