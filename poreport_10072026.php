<?php
include "include/header.php";
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
                            <div class="page-header-icon"><i data-feather="file-text"></i></div>
                            <span>Customer PO Report (Delivered Orders)</span>
                        </h1>
                    </div>
                </div>
            </div>
            <div class="container-fluid mt-2 p-0 p-2">
                <div class="card">
                    <div class="card-body p-0 p-2">
                        <div class="row">
                            <div class="col-12">
                                <form id="searchform">
                                    <div class="form-row align-items-end">

                                        <div class="col-4">
                                            <label class="small font-weight-bold text-dark">Filter by Customer</label>
                                            <select class="form-control form-control-sm" name="customerlist[]"
                                                id="customerlist" multiple>
                                                <!-- Options loaded via AJAX -->
                                            </select>

                                        </div>

                                        <div class="col-1 text-center pt-3">
                                            <span class="font-weight-bold text-muted">OR</span>
                                        </div>

                                        <div class="col-4">
                                            <label class="small font-weight-bold text-dark">Filter by Invoice Number(s)</label>
                                            <input type="text" class="form-control form-control-sm" id="invoicenumbers"
                                                name="invoicenumbers"
                                                placeholder="e.g. IV/25/02/1, IV/25/02/2, IV/25/02/3">
                                        </div>
                                    </div>
                                        <button class="btn btn-outline-dark btn-sm rounded-0 px-4 mt-3" type="button"
                                                    id="formSearchBtn">
                                                    <i class="fas fa-search"></i>&nbsp;Search
                                        </button>
                                    <input type="submit" class="d-none" id="hidesubmit">
                                </form>
                            </div>
                            <div class="col-12">
                                <hr class="border-dark">
                                <div id="targetviewdetail" style="display: none;"></div>
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
    $(document).ready(function () {

        $("#customerlist").select2({
            ajax: {
                url: 'getprocess/search_customers.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { search: params.term, page: params.page || 1 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return { results: data.results, pagination: { more: data.more } };
                },
                cache: true
            },
            placeholder: 'Search and select customers...',
            minimumInputLength: 0,
            allowClear: true,
            multiple: true
        });

        $('#formSearchBtn').click(function () {
            var customerlist   = $('#customerlist').val();
            var invoicenumbers = $.trim($('#invoicenumbers').val());

            // At least one filter must be provided
            if ((!customerlist || customerlist.length === 0) && invoicenumbers === '') {
                alert('Please select at least one customer OR enter invoice number(s).');
                return;
            }

            $('#targetviewdetail').html(
                '<div class="card border-0 shadow-none bg-transparent"><div class="card-body text-center"><img src="images/spinner.gif" alt="Loading..."></div></div>'
            ).show();

            $.ajax({
                type: "POST",
                data: {
                    customerlist:   customerlist || [],
                    invoicenumbers: invoicenumbers
                },
                url: 'getprocess/getpoorders.php',
                success: function (result) {
                    $('#targetviewdetail').html(result);

                    if ($.fn.DataTable.isDataTable('#dataTable')) {
                        $('#dataTable').DataTable().destroy();
                    }

                    if ($('#dataTable').length) {
                        $('#dataTable').DataTable({
                            "dom": "<'row'<'col-sm-5'B><'col-sm-2'l><'col-sm-5'f>>" +
                                   "<'row'<'col-sm-12'tr>>" +
                                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                            "buttons": [
                                {
                                    extend: 'csv',
                                    className: 'btn btn-success btn-sm',
                                    title: 'Customer PO Report - Delivered Orders',
                                    text: '<i class="fas fa-file-csv mr-2"></i> CSV',
                                    footer: true
                                },
                                {
                                    extend: 'pdf',
                                    className: 'btn btn-danger btn-sm',
                                    title: 'Customer PO Report - Delivered Orders',
                                    text: '<i class="fas fa-file-pdf mr-2"></i> PDF',
                                    footer: true
                                },
                                {
                                    extend: 'print',
                                    className: 'btn btn-primary btn-sm',
                                    title: 'Customer PO Report - Delivered Orders',
                                    text: '<i class="fas fa-print mr-2"></i> Print',
                                    footer: true
                                }
                            ],
                            "paging":    true,
                            "searching": true,
                            "ordering":  true,
                            "order":     [[1, 'asc']], // sort by Invoice No by default
                            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, 'All']]
                        });
                    }
                }
            });
        });
    });
</script>
<?php include "include/footer.php"; ?>