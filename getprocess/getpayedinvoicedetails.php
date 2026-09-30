<?php
require_once('../connection/db.php');
$record=$_POST['recordID'];

$sql="SELECT ip.idtbl_invoice_payment, i.invoiceno, iphi.payamount
                FROM tbl_invoice_payment AS ip
                LEFT JOIN tbl_invoice_payment_has_tbl_invoice AS iphi
                ON iphi.tbl_invoice_payment_idtbl_invoice_payment = ip.idtbl_invoice_payment
                LEFT JOIN tbl_invoice AS i
                ON i.idtbl_invoice = iphi.tbl_invoice_idtbl_invoice
                WHERE `iphi`.`tbl_invoice_payment_idtbl_invoice_payment` = '$record'";
$result=$conn->query($sql);
?>

<div class="row">
    <table id="paymentdetailstable" class="table table-striped table-bordered table-sm">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $fullTotal = 0; 
            while($row = $result->fetch_assoc()){
                $fullTotal += $row['payamount'];
            ?>
            <tr>
                <td><?php echo $row['invoiceno'] ?></td>
                <td class="text-right">Rs.<?php echo number_format($row['payamount'], 2) ?></td>
            </tr>
            <?php } ?>
            <tr class="font-weight-bold"> <td>Full Total</td>
                <td class="text-right font-weight-bold">Rs.<?php echo number_format($fullTotal, 2) ?></td>
            </tr>
        </tbody>
    </table>
</div>