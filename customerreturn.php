<?php
include "include/header.php";

$sqlreturncustomer = "SELECT `u`.`idtbl_return`,`u`.`returntype`, `u`.`returndate`, `u`.`total`, `ua`.`customer`, `u`.`acceptance_status`, `u`.`damaged_reason`, `su`.`suppliername`, `u`.`tbl_customer_idtbl_customer`, `u`.`tbl_supplier_idtbl_supplier`,`u`.`back_to_stock`,
    (SELECT `c2`.`idtbl_creditenote` FROM `tbl_creditenote_detail` AS `cd2` INNER JOIN `tbl_creditenote` AS `c2` ON (`c2`.`idtbl_creditenote` = `cd2`.`tbl_creditenote_idtbl_creditenote`) WHERE `cd2`.`tbl_return_idtbl_return` = `u`.`idtbl_return` ORDER BY `c2`.`idtbl_creditenote` DESC LIMIT 1) AS `creditnote_id`,
    (SELECT `c2`.`balAmount` FROM `tbl_creditenote_detail` AS `cd2` INNER JOIN `tbl_creditenote` AS `c2` ON (`c2`.`idtbl_creditenote` = `cd2`.`tbl_creditenote_idtbl_creditenote`) WHERE `cd2`.`tbl_return_idtbl_return` = `u`.`idtbl_return` ORDER BY `c2`.`idtbl_creditenote` DESC LIMIT 1) AS `creditnote_balance`,
    (SELECT `c2`.`settle` FROM `tbl_creditenote_detail` AS `cd2` INNER JOIN `tbl_creditenote` AS `c2` ON (`c2`.`idtbl_creditenote` = `cd2`.`tbl_creditenote_idtbl_creditenote`) WHERE `cd2`.`tbl_return_idtbl_return` = `u`.`idtbl_return` ORDER BY `c2`.`idtbl_creditenote` DESC LIMIT 1) AS `creditnote_settle`
FROM `tbl_return` as `u` LEFT JOIN `tbl_customer` AS `ua` ON (`ua`.`idtbl_customer` = `u`.`tbl_customer_idtbl_customer`) LEFT JOIN `tbl_supplier` AS `su` ON (`su`.`idtbl_supplier` = `u`.`tbl_supplier_idtbl_supplier`) WHERE `u`.`acceptance_status` IN (0,1)";
$resultreturncustomer = $conn->query($sqlreturncustomer);

include "include/topnavbar.php";
?>
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <?php include "include/menubar.php"; ?>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="page-header page-header-light bg-white shadow">
                <div class="container-fluid">
                    <div class="page-header-content py-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="corner-down-left"></i></div>
                            <span>All Return</span>
                        </h1>
                    </div>
                </div>
            </div>
            <div class="container-fluid mt-2 p-0 p-2">
                <div class="card">
                    <div class="card-body p-0 p-2">
                        <div class="row">
                            <div class="col-12">
                                <div class="scrollbar pb-3" id="style-2">
                                    <table class="table table-bordered table-striped table-sm nowrap" id="dataTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Customer name / Supplier name</th>
                                                <th>Credit Note ID</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>Remark</th>
                                                <th>Total</th>
                                                <th>Back To Stock</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($resultreturncustomer->num_rows > 0) {
                                                while ($row = $resultreturncustomer->fetch_assoc()) { ?>
                                                    <tr>
                                                        <td><?php echo $row['idtbl_return'] ?></td>
                                                        <td><?php echo $row['customer'] ? $row['customer'] : $row['suppliername']; ?></td>
                                                        <td><?php echo isset($row['creditnote_id']) && $row['creditnote_id'] ?  $row['creditnote_id'] : '-'; ?></td>
                                                        <td>
                                                            <?php
                                                            if ($row['returntype'] == 1) {
                                                                echo "Customer Return";
                                                            } else if ($row['returntype'] == 2) {
                                                                echo "Supplier Return";
                                                            } else if ($row['returntype'] == 3) {
                                                                echo "Damage Return";
                                                            }
                                                            ?>
                                                        <td><?php echo $row['returndate'] ?></td>
                                                        <td><?php echo $row['damaged_reason'] ?></td>
                                                        <td class="text-right">Rs.<?php echo number_format($row['total'], 2); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if ($row['back_to_stock'] == 1) {
                                                                echo "Yes";
                                                            } else {
                                                                echo "No";
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-primary btn-sm rounded btnView"
                                                                id="<?php echo $row['idtbl_return']; ?>"
                                                                name="<?php echo $row['acceptance_status']; ?>"><i
                                                                    class="fas fa-eye"></i></button>
                                                            <?php if ($row['acceptance_status'] == 1 && $row['returntype'] == 1 && $row['tbl_customer_idtbl_customer'] && (!$row['creditnote_id'] || ($row['creditnote_settle'] != 1 && $row['creditnote_balance'] > 0))) { ?>
                                                                <button class="btn btn-outline-secondary btn-sm btnCredit" id="<?php echo $row['idtbl_return']; ?>" data-creditnoteid="<?php echo $row['creditnote_id']; ?>" data-customerid="<?php echo $row['tbl_customer_idtbl_customer']; ?>" data-total="<?php echo $row['creditnote_balance'] ? $row['creditnote_balance'] : $row['total']; ?>" title="Apply Credit"><i class="fas fa-file-invoice-dollar"></i></button>
                                                            <?php } else { ?>
                                                                <button class="btn btn-outline-info btn-sm" disabled title="Apply Credit only for accepted customer returns with available credit"><i class="fas fa-file-invoice-dollar"></i></button>
                                                            <?php } ?>
                                                            <?php if ($row['acceptance_status'] == 0) { ?>
                                                                <button class="btn btn-outline-secondary btn-sm btnEdit"
                                                                    id="<?php echo $row['idtbl_return']; ?>"
                                                                    data-returndate="<?php echo $row['returndate']; ?>"><i
                                                                        class="fas fa-pen"></i></button>
                                                            <?php } else ?>
                                                            <?php if ($row['acceptance_status'] == 0) { ?>
                                                                <button
                                                                    data-url="process/statusacceptreturn.php?record=<?php echo $row['idtbl_return'] ?>&type=2"
                                                                    data-actiontype="8"
                                                                    class="btn btn-outline-warning btn-sm btntableaction"><i
                                                                        data-feather="x-square"></i></button>
                                                            <?php } else { ?>
                                                                <button class="btn btn-outline-success btn-sm"><i
                                                                        data-feather="check"></i></button>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                            <?php }
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
        <!-- Modal return details -->
        <div class="modal fade" id="modalreturndetails" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header p-2">
                        <h5 class="modal-title" id="viewmodaltitle"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                <div id="viewdetail"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger btn-sm" id="btnreturnprint"><i class="fas fa-print"></i>&nbsp;Print Return Note</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal order print -->
        <div class="modal fade" id="modalorderprint" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header p-2">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="viewdispatchprint"></div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-danger btn-sm fa-pull-right" id="btnorderprint"><i
                                class="fas fa-print"></i>&nbsp;Print Order</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Return -->
        <div class="modal fade" id="modaleditreturn" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal">
                <div class="modal-content">
                    <div class="modal-header p-2">
                        <h6 class="modal-title" id="viewmodaltitle">Update Return Details</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editreturnform" autocomplete="off">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="small font-weight-bold text-dark">Return Date*</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control dpd2a" placeholder=""
                                            name="editreturndate" id="editreturndate" required>
                                        <div class="input-group-append">
                                            <span class="btn btn-light border-gray-500"><i
                                                    class="far fa-calendar"></i></span>
                                        </div>
                                    </div>
                                    <input type="text" class="d-none" id="hiddenreturnid" name="hiddenreturnid">
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fa-pull-right mt-3"
                                id="btnreturnupdate"><i class="fa fa-save"></i>&nbsp;Update</button>
                            <input type="submit" class="d-none" id="hiddeneditsubmit">
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Apply Credit Note -->
        <div class="modal fade" id="modalApplyCredit" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="modalApplyCreditLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header p-2">
                        <h5 class="modal-title">Apply Credit Note</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">Credit Note ID: <strong id="creditNoteId">-</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  Return ID: <strong id="returnId">-</strong></div>
                                <div class="mb-2">Available credit from return: <strong id="creditAmountDisplay">Rs.0.00</strong></div>
                                <div id="pendingInvoicesContainer">Loading...</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        <button class="btn btn-primary btn-sm" id="btnApplyCredit">Apply Credit</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include "include/footerbar.php"; ?>
    </div>
</div>
<?php include "include/footerscripts.php"; ?>
<script>
    $(document).ready(function() {
        document.getElementById('btnorderprint').addEventListener("click", print);
        document.getElementById('btnreturnprint').addEventListener("click", printReturnNote);
        $('#dataTable').DataTable({});
        $('#dataTable tbody').on('click', '.btnEdit', function() {
            var id = $(this).attr('id');
            var returndate = $(this).data('returndate');

            $('#modaleditreturn').modal('show');
            $('#hiddenreturnid').val(id);
            $('#editreturndate').val(returndate);
        });
    })

    $('#returntype').change(function() {
        var type = $(this).val();

        if (type == 1) {
            $('#customerdiv').removeClass('d-none');
            $('#customer').prop('required', true);

            $('#supplierdiv').addClass('d-none');
            $('#supplier').prop('required', false);
        } else if (type == 2) {
            $('#customerdiv').addClass('d-none');
            $('#customer').prop('required', false);

            $('#supplierdiv').removeClass('d-none');
            $('#supplier').prop('required', true);
        } else {
            $('#customerdiv').addClass('d-none');
            $('#customer').prop('required', false);
            $('#supplierdiv').addClass('d-none');
            $('#supplier').prop('required', false);
        }
    });

    $("#btnreturnupdate").click(function() {
        if (!$("#editreturnform")[0].checkValidity()) {
            $("#hiddeneditsubmit").click();
        } else {
            var returndate = $('#editreturndate').val();
            var returnId = $('#hiddenreturnid').val();

            $.ajax({
                type: "POST",
                data: {
                    returndate: returndate,
                    returnId: returnId
                },
                url: 'process/updatecustomerreturnprocess.php',
                success: function(result) { // alert(result)
                    var obj = JSON.parse(result);
                    if (obj.status == 1) {
                        actionreload(obj.action);
                    } else {
                        action(obj.action);
                    }
                }
            });
        }
    });

    $('#dataTable tbody').on('click', '.btnView', function() {
        var id = $(this).attr('id');
        var acceptancestatus = $(this).attr('name');
        // Store current return ID for printing
        $('#modalreturndetails').data('returnId', id);
        // alert("asd")
        $.ajax({
            type: "POST",
            data: {
                recordID: id
            },
            url: 'getprocess/getreturndetails.php',
            success: function(result) {
                // alert(result)
                $('#viewmodaltitle').html('Return No ' + id)
                $('#viewdetail').html(result);
                $('#modalreturndetails').modal('show');
                if (acceptancestatus == 1) {
                    $('#submitBtn').attr('disabled', true);
                } else {
                    $('#submitBtn').attr('disabled', false);
                }

            }
        });
    });

    // Open Apply Credit modal and load pending invoices for selected customer
    $('#dataTable tbody').on('click', '.btnCredit', function() {
        var returnId = $(this).attr('id');
        var customerId = $(this).data('customerid');
        var creditNoteId = parseInt($(this).data('creditnoteid')) || 0;
        var total = parseFloat($(this).data('total')) || 0;
        $('#creditNoteId').text(creditNoteId ? creditNoteId : '-');
        $('#returnId').text(returnId ? returnId : '-');
        $('#creditAmountDisplay').text('Rs.' + addCommas(total.toFixed(2)));
        $('#modalApplyCredit').data('returnId', returnId).data('customerId', customerId).data('creditNoteId', creditNoteId).data('creditTotal', total);
        $('#pendingInvoicesContainer').html('Loading...');
        $.ajax({
            type: 'POST',
            url: 'getprocess/getcustomerpendinginvoices.php',
            data: {
                customerID: customerId
            },
            success: function(res) {
                $('#pendingInvoicesContainer').html(res);
                $('#modalApplyCredit').modal('show');
            },
            error: function() {
                $('#pendingInvoicesContainer').html('Error loading invoices');
                $('#modalApplyCredit').modal('show');
            }
        });
    });

    // Apply credit allocations to invoices
    $('#btnApplyCredit').click(function() {
        var returnId = $('#modalApplyCredit').data('returnId');
        var customerId = $('#modalApplyCredit').data('customerId');
        var creditTotal = parseFloat($('#modalApplyCredit').data('creditTotal')) || 0;
        var allocations = [];
        var sum = 0;
        $('#pendingInvoicesContainer').find('input.allocAmt').each(function() {
            var inv = $(this).data('invoiceid');
            var val = parseFloat($(this).val()) || 0;
            if (val > 0) {
                allocations.push({
                    invoiceID: inv,
                    amount: val
                });
                sum += val;
            }
        });
        if (allocations.length == 0) {
            if (!confirm('No allocations entered. Create a credit note without applying now?')) return;
        }
        if (sum > creditTotal) {
            alert('Allocated amount exceeds available credit');
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'process/returncreatenote.php',
            data: {
                returnId: returnId,
                customerId: customerId,
                creditNoteId: $('#modalApplyCredit').data('creditNoteId') || 0,
                allocations: JSON.stringify(allocations),
                creditTotal: creditTotal
            },
            success: function(resp) {
                try {
                    var obj = JSON.parse(resp);
                    if (obj.success || (obj.action && obj.action.type === 'success')) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: (obj.action && obj.action.message) ? obj.action.message : 'Credit applied successfully',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        var errorMessage = obj.message ||
                            (obj.action ? JSON.parse(obj.action).message : resp);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (e) {
                    alert('Error: ' + resp);
                }
            },
            error: function() {
                alert('Server error');
            }
        });
    });

    function print() {
        printJS({
            printable: 'viewdispatchprint',
            type: 'html',
            style: '@page { size: portrait; margin:0.25cm; }',
            targetStyles: ['*']
        })
    }

    function printReturnNote() {
        var returnId = $('#modalreturndetails').data('returnId');

        if (!returnId) {
            alert("Please select a return note first");
            return;
        }

        $.ajax({
            type: "POST",
            data: {
                recordID: returnId
            },
            url: 'getprocess/getreturnprint.php',
            success: function(result) {
                var printWindow = window.open('', '', 'height=600,width=900');
                printWindow.document.write('<html><head><link rel="stylesheet" href="css/bootstrap.min.css"><style>body{font-size:13px;} .tableprint {border-collapse:collapse;} .tableprint td, .tableprint th {padding:4px;} @media print {body {margin:0; padding:10px;} .btn {display:none;}}</style></head><body>');
                printWindow.document.write(result);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                setTimeout(function() {
                    printWindow.print();
                }, 250);
            },
            error: function(xhr, status, error) {
                alert("Error loading print layout. Please try again.");
                console.log(error);
            }
        });
    }
    $('.dpd2a').datepicker({
        uiLibrary: 'bootstrap4',
        autoclose: 'true',
        todayHighlight: true,
        format: 'yyyy-mm-dd'
    });



    function addCommas(nStr) {
        nStr += '';
        x = nStr.split('.');
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }

    function accept_confirm() {
        return confirm("Are you sure you want to Accept this?");
    }

    function deactive_confirm() {
        return confirm("Are you sure you want to deactive this?");
    }

    function active_confirm() {
        return confirm("Are you sure you want to active this?");
    }

    function delete_confirm() {
        return confirm("Are you sure you want to remove this?");
    }

    function company_confirm() {
        return confirm("Are you sure this product send to company?");
    }

    function warehouse_confirm() {
        return confirm("Are you sure this product back to warehouse?");
    }

    function customer_confirm() {
        return confirm("Are you sure this product breturn back to customer?");
    }

    function credit_confirm() {
        return confirm("Are you sure you want to create credit note?");
    }
</script>
<?php include "include/footer.php"; ?>