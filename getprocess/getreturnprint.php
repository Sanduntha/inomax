<?php
session_start();
require_once('../connection/db.php');

$record = $_POST['recordID'];

$fulltot = 0;
$discount = 0;

// Get return header information
$sqlReturnInfo = "SELECT `u`.`idtbl_return`, `u`.`returntype`, `u`.`returndate`, `u`.`total`, `u`.`damaged_reason`, `ua`.`customer`, `ua`.`address`, `su`.`suppliername`, `su`.`address` as `supplier_address` 
FROM `tbl_return` as `u` 
LEFT JOIN `tbl_customer` AS `ua` ON (`ua`.`idtbl_customer` = `u`.`tbl_customer_idtbl_customer`) 
LEFT JOIN `tbl_supplier` AS `su` ON (`su`.`idtbl_supplier` = `u`.`tbl_supplier_idtbl_supplier`) 
WHERE `u`.`idtbl_return` = '$record'";
$resultReturnInfo = $conn->query($sqlReturnInfo);
$rowReturnInfo = $resultReturnInfo->fetch_assoc();

// Get return details
$sqlReturnDetails = "SELECT `d`.`actualqty`, `d`.`idtbl_return_details`, `p`.`product_name`, `d`.`unitprice`, `d`.`qty`, `d`.`discount`, `d`.`total` 
FROM `tbl_return` as `r` 
JOIN `tbl_return_details` as `d` ON (`r`.`idtbl_return` = `d`.`tbl_return_idtbl_return`) 
JOIN `tbl_product` as `p` ON (`d`.`tbl_product_idtbl_product` = `p`.`idtbl_product`) 
WHERE `d`.`tbl_return_idtbl_return` = '$record'";
$resultReturnDetails = $conn->query($sqlReturnDetails);

// Determine return type
$returnTypeText = '';
if ($rowReturnInfo['returntype'] == 1) {
    $returnTypeText = "CUSTOMER RETURN";
} else if ($rowReturnInfo['returntype'] == 2) {
    $returnTypeText = "SUPPLIER RETURN";
} else if ($rowReturnInfo['returntype'] == 3) {
    $returnTypeText = "DAMAGE RETURN";
}

// Determine party name
$partyName    = $rowReturnInfo['customer']    ? $rowReturnInfo['customer']    : $rowReturnInfo['suppliername'];
$partyAddress = $rowReturnInfo['customer']    ? $rowReturnInfo['address']     : $rowReturnInfo['supplier_address'];
?>
<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 13px;
        color: #000;
        margin: 0;
        padding: 10px;
    }

    /* ── Header / Company Block ── */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }

    .header-table td {
        padding: 4px 6px;
        vertical-align: middle;
    }

    .company-name {
        font-size: 20px;
        font-weight: bold;
        margin: 0 0 4px 0;
    }

    .company-info {
        font-size: 12px;
        line-height: 1.5;
    }

    .doc-title {
        text-align: center;
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 8px 0;
        text-transform: uppercase;
    }

    .info-bar {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        margin-bottom: 12px;
    }

    .info-bar td {
        padding: 8px 10px;
        vertical-align: top;
    }

    .info-bar .left-cell {
        width: 60%;
    }

    .info-bar .right-cell {
        width: 40%;
        text-align: right;
        border-left: 1px solid #000;
    }

    .info-bar .info-row {
        margin-bottom: 4px;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    .details-table thead tr {
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
    }

    .details-table thead th {
        padding: 7px 8px;
        font-size: 13px;
        white-space: nowrap;
    }

    .details-table tbody tr {
        border-bottom: 1px solid #ccc;
    }

    .details-table tbody td {
        padding: 6px 8px;
        font-size: 13px;
    }

    .details-table tfoot tr {
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
    }

    .details-table tfoot th {
        padding: 7px 8px;
        font-size: 13px;
    }

    .col-no        { width: 4%;  text-align: center; }
    .col-product   { width: 28%; text-align: left;   }
    .col-uprice    { width: 14%; text-align: right;  }
    .col-qty       { width: 8%;  text-align: center; }
    .col-actualqty { width: 10%; text-align: center; }
    .col-discount  { width: 10%; text-align: center; }
    .col-total     { width: 14%; text-align: right;  }

    .details-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #000;
        margin-top: 20px;
    }

    .signature-table td {
        width: 33.33%;
        padding: 12px 10px 8px;
        vertical-align: bottom;
        height: 80px;
    }

    .signature-table td:not(:last-child) {
        border-right: 1px solid #000;
    }

    .signature-line {
        display: inline-block;
        width: 140px;
        border-bottom: 1px solid #000;
        margin-top: 30px;
    }

    @media print {
        body { margin: 0; padding: 10px; }

        .details-table,
        .header-table,
        .info-bar,
        .signature-table { page-break-inside: avoid; }
    }
</style>

<table class="header-table">
    <tbody>
        <tr>
            <td style="width:90px;">
                <img src="images/logo.png" width="80" height="80" style="display:block;">
            </td>
            <td>
                <p class="company-name">ENOMAX Holdings (PVT) LTD</p>
                <div class="company-info">
                    No.46, Garden City<br>
                    Minuwangoda Road, Ja-ela.<br>
                    Tel: 011-3468568
                </div>
            </td>
        </tr>
    </tbody>
</table>

<div class="doc-title"><?php echo $returnTypeText; ?> NOTE</div>

<table class="info-bar">
    <tr>
        <td class="left-cell">
            <div class="info-row"><strong>Party:</strong> <?php echo htmlspecialchars($partyName); ?></div>
            <div class="info-row"><strong>Address:</strong> <?php echo htmlspecialchars($partyAddress); ?></div>
        </td>
        <td class="right-cell">
            <div class="info-row"><strong>Date:</strong> <?php echo $rowReturnInfo['returndate']; ?></div>
            <div class="info-row"><strong>Return No:</strong> RET_<?php echo $rowReturnInfo['idtbl_return']; ?></div>
            <?php if (!empty($rowReturnInfo['damaged_reason'])): ?>
            <div class="info-row"><strong>Remark:</strong> <?php echo htmlspecialchars($rowReturnInfo['damaged_reason']); ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>

<table class="details-table">
    <thead>
        <tr>
            <th class="col-no">#</th>
            <th class="col-product" style="text-align:left;">Product</th>
            <th class="col-uprice">Unit Price</th>
            <th class="col-qty">Qty</th>
            <th class="col-actualqty">Actual Qty</th>
            <th class="col-discount">Discount %</th>
            <th class="col-total">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $srNo = 1;
        while ($rowDetails = $resultReturnDetails->fetch_assoc()):
            $fulltot += $rowDetails['total'];
            $actualQtyText = ($rowDetails['actualqty'] == -2) ? 'Not Defined' : $rowDetails['actualqty'];
        ?>
        <tr>
            <td class="col-no"><?php echo $srNo; ?></td>
            <td class="col-product"><?php echo htmlspecialchars($rowDetails['product_name']); ?></td>
            <td class="col-uprice">Rs. <?php echo number_format($rowDetails['unitprice'], 2); ?></td>
            <td class="col-qty"><?php echo $rowDetails['qty']; ?></td>
            <td class="col-actualqty"><?php echo $actualQtyText; ?></td>
            <td class="col-discount"><?php echo $rowDetails['discount']; ?>%</td>
            <td class="col-total">Rs. <?php echo number_format($rowDetails['total'], 2); ?></td>
        </tr>
        <?php
            $srNo++;
        endwhile;
        ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="6" style="text-align:right; padding-right:10px;">Net Total</th>
            <th class="col-total">Rs. <?php echo number_format($fulltot, 2); ?></th>
        </tr>
    </tfoot>
</table>

<table class="signature-table">
    <tr>
        <td style="text-align:left;">
            <strong>Prepared By:</strong><br>
            <span class="signature-line"></span>
        </td>
        <td style="text-align:center;">
            <strong>Approved By:</strong><br>
            <span class="signature-line"></span>
        </td>
        <td style="text-align:right;">
            <strong>Received By:</strong><br>
            <span class="signature-line"></span>
        </td>
    </tr>
</table>