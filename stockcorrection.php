<?php
session_start();
if(!isset($_SESSION['userid'])){header("Location:index.php");exit;}
include "include/header.php";
include "include/topnavbar.php";
?>

<div id="layoutSidenav" >
    <div id="layoutSidenav_nav">
        <?php include "include/menubar.php"; ?>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="page-header page-header-light bg-white shadow">
                <div class="container-fluid">
                    <div class="page-header-content py-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="edit-3"></i></div>
                            <span>Stock Manual Correction</span>
                        </h1>
                    </div>
                </div>
            </div>
            <div class="container-fluid mt-2 p-0 p-2">
                <div class="card">
                    <div class="card-body p-0 p-2">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-row align-items-end">
                                    <div class="col-3">
                                        <label class="small font-weight-bold text-dark">Category</label>
                                        <div class="input-group input-group-sm mb-3">
                                            <select class="form-control rounded-0" id="categoryfilter" name="categoryfilter" style="width:100%">
                                                <option value="">-- All Categories --</option>
                                                <?php
                                                include "connection/db.php";
                                                $sqlcat = "SELECT `idtbl_product_category`, `category` FROM `tbl_product_category` WHERE `status`=1 ORDER BY `category` ASC";
                                                $resultcat = $conn->query($sqlcat);
                                                while ($rowcat = $resultcat->fetch_assoc()) {
                                                    echo '<option value="' . $rowcat['idtbl_product_category'] . '">' . htmlspecialchars($rowcat['category']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <label class="small font-weight-bold text-dark">Product*</label>
                                        <div class="input-group input-group-sm mb-3">
                                            <select class="form-control rounded-0" id="productfilter" name="productfilter" style="width:100%">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr class="border-dark">
                                <div id="targetviewdetail" style="display:none;">
                                    <table id="dataTable" class="table table-striped table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Batch No</th>
                                                <th>Batch Qty</th>
                                                <th class="text-center">Available Stock</th>
                                                <th>New Available Stock</th>
                                                <th class="text-center">Corrected</th>
                                                <th>Corrected By</th>
                                                <th>Corrected At</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stockCorrectionBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include "include/footerbar.php"; ?>
    </div>
</div>

<?php include "include/footerscripts.php"; ?>
<script>
$(document).ready(function() {
    $('#categoryfilter').select2({
        placeholder: '-- All Categories --',
        allowClear: true,
        width: 'resolve'
    });

    function initProductSelect2() {
        $('#productfilter').select2({
            placeholder: '-- Search &amp; Select Product --',
            allowClear: true,
            width: 'resolve',
            minimumInputLength: 0,
            ajax: {
                url: 'getprocess/getprodbycategory.php',
                type: 'POST',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1,
                        categoryId: $('#categoryfilter').val()
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: { more: data.pagination.more }
                    };
                },
                cache: true
            }
        });
    }
    initProductSelect2();

    $('#categoryfilter').on('change', function() {
        $('#productfilter').val(null).trigger('change');
        $('#targetviewdetail').hide();
    });

    // Product change -> load batch-wise stock
    $('#productfilter').on('change', function() {
        var productId = $(this).val();

        if (!productId) {
            $('#targetviewdetail').hide();
            return;
        }

        $('#stockCorrectionBody').html('<tr><td colspan="8" class="text-center"><img src="images/spinner.gif" alt=""></td></tr>');
        $('#targetviewdetail').show();

        loadBatches(productId);
    });

    function loadBatches(productId) {
        $.ajax({
            type: "POST",
            data: { productId: productId },
            url: 'getprocess/getstockbatches.php',
            success: function(result) {
                $('#stockCorrectionBody').html(result);
            }
        });
    }

    // Update button (delegated - rows are loaded dynamically)
    $(document).on('click', '.updateStockBtn', function() {
        var btn = $(this);
        var row = btn.closest('tr');
        var stockId = row.data('stockid');
        var batchNo = row.data('batchno');
        var productId = $('#productfilter').val();
        var newQty = row.find('.newQtyInput').val();

        if (newQty === '' || newQty === null || isNaN(newQty) || newQty < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid value',
                text: 'Please enter a valid available stock value.',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Confirm update',
            text: 'Confirm updating available stock for batch ' + batchNo + ' to ' + newQty + '?',
            showCancelButton: true,
            confirmButtonText: 'Yes, update',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#7015bb',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            btn.prop('disabled', true);

            $.ajax({
                type: "POST",
                data: {
                    idtbl_stock: stockId,
                    newqty: newQty
                },
                url: 'process/updatestockcorrection.php',
                dataType: 'json',
                success: function(result) {
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Stock updated',
                            text: 'Batch ' + batchNo + ' available stock has been updated.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadBatches(productId);
                    } else if (result.status === 'locked') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Locked',
                            text: 'This batch was already manually corrected by ' + result.locked_by + '. Only the system administrator can correct it again.',
                            confirmButtonColor: '#dc3545'
                        });
                        btn.prop('disabled', false);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update failed',
                            text: 'Please try again.',
                            confirmButtonColor: '#dc3545'
                        });
                        btn.prop('disabled', false);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Something went wrong',
                        text: 'Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                    btn.prop('disabled', false);
                }
            });
        });
    });

});
</script>
<?php include "include/footer.php"; ?>