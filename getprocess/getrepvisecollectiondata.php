<?php 
session_start();
require_once('../connection/db.php');
$fromdate = $_POST['fromdate'];
$todate = $_POST['todate'];
$today = date("Y-m-d");
$replist = $_POST['replist'];
$replist = implode(", ", $replist);


// $sql =    "SELECT  
//                 `u`.`date`, 
//                 `c`.`name` AS `cusname`, 
//                 `e`.`name` AS `empname`, 
//                 `d`.`receiptno`, 
//                 `i`.`invoiceno`, 
//                 SUM(`d`.`amount`) as `payamount`,
//                 'INVOICE' AS `type`
//             FROM `tbl_invoice_payment` AS `u`  
//             LEFT JOIN `tbl_invoice_payment_has_tbl_invoice` AS `p` 
//                 ON `p`.`tbl_invoice_payment_idtbl_invoice_payment` = `u`.`idtbl_invoice_payment` 
//             LEFT JOIN `tbl_invoice` AS `i` 
//                 ON `i`.`idtbl_invoice` = `p`.`tbl_invoice_idtbl_invoice` 
//             LEFT JOIN `tbl_customer_order` AS `o` 
//                 ON `i`.`tbl_customer_order_idtbl_customer_order` = `o`.`idtbl_customer_order` 
//             LEFT JOIN `tbl_customer` AS `c` 
//                 ON `i`.`tbl_customer_idtbl_customer` = `c`.`idtbl_customer`
//             LEFT JOIN `tbl_employee` AS `e` 
//                 ON `o`.`tbl_employee_idtbl_employee` = `e`.`idtbl_employee`
//             WHERE `i`.`status` IN (1, 2) 
//                 AND `u`.`date` BETWEEN '$fromdate' AND '$todate'
//                 AND `o`.`tbl_employee_idtbl_employee` IN ($replist)
//                 AND `d`.`method` != '3'
//                 AND `d`.`method` != '4'
//             GROUP BY `p`.`tbl_invoice_payment_idtbl_invoice_payment`";
// $sql =    "SELECT `tbl_invoice_payment`.`date`, `tbl_invoice_payment_detail`.`tbl_invoice_payment_idtbl_invoice_payment`, `tbl_invoice_payment_detail`.`receiptno`, `tbl_invoice_payment_detail`.`amount` AS `payamount`, `tbl_invoice`.`invoiceno`, `tbl_customer`.`name` AS `cusname`, `tbl_employee`.`name` AS `empname`, 'INVOICE' AS `type` 
//            FROM `tbl_invoice_payment_detail` 
//            LEFT JOIN `tbl_invoice_payment` ON `tbl_invoice_payment`.`idtbl_invoice_payment` = `tbl_invoice_payment_detail`.`tbl_invoice_payment_idtbl_invoice_payment` 
//            LEFT JOIN `tbl_invoice_payment_has_tbl_invoice` ON `tbl_invoice_payment_has_tbl_invoice`.`tbl_invoice_payment_idtbl_invoice_payment` = `tbl_invoice_payment`.`idtbl_invoice_payment` 
//            LEFT JOIN `tbl_invoice` ON `tbl_invoice`.`idtbl_invoice` = `tbl_invoice_payment_has_tbl_invoice`.`tbl_invoice_idtbl_invoice` 
//            LEFT JOIN `tbl_customer_order` ON `tbl_invoice`.`tbl_customer_order_idtbl_customer_order` = `tbl_customer_order`.`idtbl_customer_order` 
//            LEFT JOIN `tbl_customer` ON `tbl_invoice`.`tbl_customer_idtbl_customer` = `tbl_customer`.`idtbl_customer`
//            LEFT JOIN `tbl_employee` ON `tbl_customer_order`.`tbl_employee_idtbl_employee` = `tbl_employee`.`idtbl_employee`
//            WHERE `tbl_invoice_payment_detail`.`status` = 1 
//            AND `tbl_invoice_payment`.`date` BETWEEN '$fromdate' AND '$todate'
//            AND `tbl_invoice_payment_detail`.`method` NOT IN (3, 4) 
//            AND `tbl_customer_order`.`tbl_employee_idtbl_employee` IN ($replist)
//            GROUP BY `tbl_invoice_payment`.`idtbl_invoice_payment`";


$sql =    "SELECT tb1.idtbl_invoice_payment, tb1.date, tb1.receiptno, tb1.payamount, tb1.method, tb2.invoiceno, tb2.cusname, tb2.empname, 'INVOICE' AS type
            FROM (
                SELECT ip.idtbl_invoice_payment, ip.date, ipd.receiptno, ipd.method, ipd.amount AS payamount
                FROM tbl_invoice_payment_detail AS ipd
                LEFT JOIN  tbl_invoice_payment AS ip
                ON ip.idtbl_invoice_payment = ipd.tbl_invoice_payment_idtbl_invoice_payment
                WHERE ipd.status = 1
                    AND ip.date  BETWEEN '$fromdate' AND '$todate'
                    AND ipd.method NOT IN (3, 4)
            ) AS tb1
            LEFT JOIN (
                SELECT ip.idtbl_invoice_payment, i.invoiceno, c.customer AS cusname, e.name AS empname
                FROM tbl_invoice_payment AS ip
                LEFT JOIN tbl_invoice_payment_has_tbl_invoice AS iphi
                ON iphi.tbl_invoice_payment_idtbl_invoice_payment = ip.idtbl_invoice_payment
                LEFT JOIN tbl_invoice AS i
                ON i.idtbl_invoice = iphi.tbl_invoice_idtbl_invoice
                LEFT JOIN tbl_customer_order AS co
                ON i.tbl_customer_order_idtbl_customer_order = co.idtbl_customer_order
                LEFT JOIN tbl_customer AS c
                ON i.tbl_customer_idtbl_customer = c.idtbl_customer
                LEFT JOIN  tbl_employee AS e
                ON co.tbl_employee_idtbl_employee = e.idtbl_employee
                WHERE co.tbl_employee_idtbl_employee  IN ($replist)
                GROUP BY ip.idtbl_invoice_payment
            ) AS tb2
            ON tb1.idtbl_invoice_payment = tb2.idtbl_invoice_payment
            WHERE 
            tb2.idtbl_invoice_payment IS NOT NULL";

$result = $conn->query($sql);


if ($result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped table-sm nowrap" id="dataTable">
            <thead>
                 <tr>
                    <th>#</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Receipt No</th>
                    <th class="text-center">Method</th>
                    <th class="text-center">Rep</th>
                    <th class="text-center">Customer Name</th>
                    <th class="text-center">Type</th>
                    <th class="text-right">Payment</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>';
    $c=0;
    $fullPayment=0;
    
    while ($rowstock = $result->fetch_assoc()) {
        $fullPayment += $rowstock['payamount'];
        $methodText = '';

        if($rowstock['method'] == 1){
            $methodText = 'Cash';
        }else if($rowstock['method'] == 2){
            $methodText = 'Cheque';
        }else if($rowstock['method'] == 3){
            $methodText = 'Credit Note';
        }
        $c++;
        echo '<tr>
                <td class="text-center">' . $c . '</td>
                <td class="text-center">' . $rowstock['date'] . '</td>
                <td class="text-center">' . $rowstock['receiptno'] . '</td>
                <td class="text-center">' . $methodText . '</td>
                <td class="text-center">' . $rowstock['empname'] . '</td>
                <td class="text-center">' . $rowstock['cusname'] . '</td>
                <td class="text-center">' . $rowstock['type'] . '</td>
                <td class="text-right">' . number_format($rowstock['payamount'], 2, '.', ',')  . '</td>
                <td class="text-right"><button id="' . $rowstock['idtbl_invoice_payment'] . '" class="btn btn-outline-primary btn-sm  btnpaymentdetails"><i class="fas fa-eye"</i></button></td>
            </tr>';
    }
    echo '</tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-center"><strong>Total</strong></td>
                        <td class="text-right"><strong>' . number_format($fullPayment, 2) . '</strong></td>
                    </tr>
                </tfoot>
            </table>';
} else {
    echo '<div class="alert alert-info" role="alert">No records found.</div>';
}
?>



