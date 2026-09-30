<?php 
session_start();
require_once('../connection/db.php');

$fromdate = $_POST['fromdate'];
$todate = $_POST['todate'];
$today = date("Y-m-d");
$replist = $_POST['replist'];
$replist = implode(", ", $replist);

$sql =    "SELECT  
                `u`.`date`, 
                `c`.`name` AS `cusname`, 
                `e`.`name` AS `empname`, 
                `d`.`receiptno`, 
                `u`.`payment`,
                'INVOICE' AS `type`
            FROM `tbl_invoice_payment` AS `u`  
            LEFT JOIN `tbl_invoice_payment_has_tbl_invoice` AS `p` 
                ON `p`.`tbl_invoice_payment_idtbl_invoice_payment` = `u`.`idtbl_invoice_payment` 
            LEFT JOIN `tbl_invoice` AS `i` 
                ON `i`.`idtbl_invoice` = `p`.`tbl_invoice_idtbl_invoice` 
            LEFT JOIN `tbl_customer_order` AS `o` 
                ON `i`.`tbl_customer_order_idtbl_customer_order` = `o`.`idtbl_customer_order` 
            LEFT JOIN `tbl_invoice_payment_detail` AS `d` 
                ON `d`.`tbl_invoice_payment_idtbl_invoice_payment` = `u`.`idtbl_invoice_payment` 
            LEFT JOIN `tbl_customer` AS `c` 
                ON `i`.`tbl_customer_idtbl_customer` = `c`.`idtbl_customer`
            LEFT JOIN `tbl_employee` AS `e` 
                ON `o`.`tbl_employee_idtbl_employee` = `e`.`idtbl_employee`
            WHERE `i`.`status` IN (1, 2) 
                AND `u`.`date` BETWEEN '$fromdate' AND '$todate'
                AND `o`.`tbl_employee_idtbl_employee` IN ($replist)
                AND `d`.`method` != '3'
            GROUP BY `d`.`receiptno`";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped table-sm nowrap" id="dataTable">
            <thead>
                 <tr>
                    <th>#</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Rep</th>
                    <th class="text-center">Customer Name</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Receipt No</th>
                    <th class="text-right">Payment</th>
                </tr>
            </thead>
            <tbody>';
    $c=0;
    $fullPayment=0;
    
    while ($rowstock = $result->fetch_assoc()) {
        $fullPayment += $rowstock['payment'];
        $c++;
        echo '<tr>
                <td class="text-center">' . $c . '</td>
                <td class="text-center">' . $rowstock['date'] . '</td>
                <td class="text-center">' . $rowstock['empname'] . '</td>
                <td class="text-center">' . $rowstock['cusname'] . '</td>
                <td class="text-center">' . $rowstock['type'] . '</td>
                <td class="text-center">' . $rowstock['receiptno'] . '</td>
                <td class="text-right">' . number_format($rowstock['payment'], 2, '.', ',')  . '</td>
            </tr>';
    }
    echo '</tbody>
                <tfoot>
                    <tr>
                        <td colspan="6
                        " class="text-center"><strong>Total</strong></td>
                        <td class="text-right"><strong>' . number_format($fullPayment, 2) . '</strong></td>
                    </tr>
                </tfoot>
            </table>';
} else {
    echo '<div class="alert alert-info" role="alert">No records found.</div>';
}
?>
