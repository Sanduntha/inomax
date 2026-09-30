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
                            <div class="page-header-icon"><i data-feather="percent"></i></div>
                            <span>Customer Discount Report</span>
                        </h1>
                    </div>
                </div>
            </div>

            <div class="container-fluid mt-2 p-0 p-2">
                <div class="card">
                    <div class="card-body p-0 p-2">

                        <div class="row">
                            <div class="col-12">
                                <form id="discountReportForm">
                                    <div class="form-row align-items-end">

                                        <!-- Customer -->
                                        <div class="col-4">
                                            <label class="small font-weight-bold text-dark">Customer*</label>
                                            <select class="form-control form-control-sm" name="selectCustomer" id="selectCustomer">
                                                <option value="0">-- Select Customer --</option>
                                            </select>
                                        </div>

                                        <!-- Date From -->
                                        <div class="col-3">
                                            <label class="small font-weight-bold text-dark">From*</label>
                                            <input type="date" class="form-control form-control-sm" name="fromdate" id="fromdate" required>
                                        </div>

                                        <!-- Date To -->
                                        <div class="col-3">
                                            <label class="small font-weight-bold text-dark">To*</label>
                                            <input type="date" class="form-control form-control-sm" name="todate" id="todate" required>
                                        </div>

                                        <!-- Submit -->
                                        <div class="col-2">
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" id="submitBtn">
                                                <i class="fas fa-search"></i>&nbsp;View
                                            </button>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>

                        <hr class="mt-2 mb-2">

                        <!-- KPI Summary Cards -->
                        <div class="row mb-2" id="summaryCards" style="display:none !important;">
                            <div class="col-6 col-md-2">
                                <div class="card border-left-primary shadow-sm py-1 px-2">
                                    <div class="small font-weight-bold text-primary text-uppercase mb-1">Gross Total</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800" id="kpiGross">0.00</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="card border-left-warning shadow-sm py-1 px-2">
                                    <div class="small font-weight-bold text-warning text-uppercase mb-1">PO Discount</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800" id="kpiPO">0.00</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="card border-left-danger shadow-sm py-1 px-2">
                                    <div class="small font-weight-bold text-danger text-uppercase mb-1">Item Discount</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800" id="kpiItem">0.00</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="card border-left-danger shadow-sm py-1 px-2">
                                    <div class="small font-weight-bold text-danger text-uppercase mb-1">Total Discount</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800" id="kpiTotalDisc">0.00</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="card border-left-success shadow-sm py-1 px-2">
                                    <div class="small font-weight-bold text-success text-uppercase mb-1">Net Total</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800" id="kpiNet">0.00</div>
                                </div>
                            </div>
                        </div>

                        <div id="targetviewdetail"></div>

                    </div>
                </div>
            </div>
        </main>
        <?php include "include/footerbar.php"; ?>
    </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printreport" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Discount Report PDF</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="embed-responsive embed-responsive-16by9" id="frame">
                    <iframe class="embed-responsive-item" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "include/footerscripts.php"; ?>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .select2-container .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px) !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.2rem !important;
        font-size: 0.875rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.5rem) !important;
        font-size: 0.875rem !important;
        color: #495057 !important;
        padding-left: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.5rem) !important;
    }
    .select2-container--default .select2-results__option { font-size: 0.875rem !important; padding: 4px 8px !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        font-size: 0.875rem !important; border: 1px solid #ced4da !important;
        border-radius: 0.2rem !important; padding: 3px 6px !important;
    }
    .select2-dropdown { border: 1px solid #ced4da !important; border-radius: 0.2rem !important; box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #005EB8 !important; }
    .select2-container { width: 100% !important; }

    #discountReportTable tfoot tr { background-color: #343a40; color: #fff; }
    #discountReportTable tfoot tr td { font-weight: bold; }

    /* Child row inner table */
    .inner-table { width:100%; font-size:0.78rem; border-collapse:collapse; margin-top:4px; }
    .inner-table thead th { background-color:#dee2e6; padding:5px 8px; text-align:center; font-weight:600; border:1px solid #ced4da; }
    .inner-table tbody td { padding:4px 8px; border:1px solid #e9ecef; text-align:right; }
    .inner-table tbody td:first-child, .inner-table tbody td:nth-child(2) { text-align:left; }
    .inner-table tfoot td { background:#f1f3f5; font-weight:bold; padding:4px 8px; border:1px solid #ced4da; text-align:right; }
    .inner-table tbody tr:hover { background-color:#e8f0fe; }

    /* KPI cards */
    .border-left-primary { border-left:4px solid #4e73df !important; }
    .border-left-warning { border-left:4px solid #f6c23e !important; }
    .border-left-danger  { border-left:4px solid #e74a3b !important; }
    .border-left-success { border-left:4px solid #1cc88a !important; }
    .border-left-info    { border-left:4px solid #36b9cc !important; }
</style>

<script>
$(document).ready(function () {

    $('#selectCustomer').select2({
        placeholder: '-- Select Customer --',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        ajax: {
            url: 'getprocess/search_customers.php',
            dataType: 'json',
            delay: 250,
            cache: true,
            data: function (params) {
                return { q: params.term || '', page: params.page || 1 };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.results, pagination: data.pagination };
            }
        }
    });

    function buildChildRow(items) {
        if (!items || items.length === 0) {
            return '<p class="text-muted small m-2">No item details available.</p>';
        }
        var totalDisc = 0, totalLine = 0;
        var html = '<div style="padding:8px 16px;background:#f8f9fa;">';
        html += '<table class="inner-table">';
        html += '<thead><tr>';
        html += '<th style="text-align:left">Item Code</th>';
        html += '<th style="text-align:left">Product</th>';
        html += '<th>Qty</th><th>Unit Price</th><th>Sale Price</th>';
        html += '<th>Item Discount</th><th>Line Total</th>';
        html += '</tr></thead><tbody>';

        $.each(items, function (i, item) {
            var disc = item.item_disc !== '—' ? parseFloat(item.item_disc.replace(/,/g,'')) : 0;
            var line = parseFloat(item.item_total.replace(/,/g,''));
            totalDisc += disc;
            totalLine += line;

            html += '<tr>';
            html += '<td style="text-align:left">' + $('<span>').text(item.itemcode).html() + '</td>';
            html += '<td style="text-align:left">' + $('<span>').text(item.product_name).html() + '</td>';
            html += '<td>' + item.qty + '</td>';
            html += '<td>' + item.unitprice + '</td>';
            html += '<td>' + item.saleprice + '</td>';
            html += '<td>' + item.item_disc + '</td>';
            html += '<td>' + item.item_total + '</td>';
            html += '</tr>';
        });

        var fmt = function(n){ return n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); };
        html += '</tbody><tfoot><tr>';
        html += '<td colspan="5" style="text-align:right;">Total</td>';
        html += '<td>' + fmt(totalDisc) + '</td>';
        html += '<td>' + fmt(totalLine) + '</td>';
        html += '</tr></tfoot>';
        html += '</table></div>';
        return html;
    }

    $('#discountReportForm').submit(function (e) {
        e.preventDefault();

        var customerID = $('#selectCustomer').val();
        var fromdate   = $('#fromdate').val();
        var todate     = $('#todate').val();

        if (!customerID) {
            alert('Please select a customer.');
            return;
        }
        if (!fromdate || !todate) {
            alert('Please select a date range.');
            return;
        }

        $('#targetviewdetail').html(
            "<div class='text-center py-4'><div class='spinner-border text-primary' role='status'></div><p class='mt-2 small'>Loading...</p></div>"
        );
        $('#summaryCards').hide();

        $.ajax({
            type: 'POST',
            url:  'getprocess/getdiscountreport.php',
            data: { customer: customerID, validfrom: fromdate, validto: todate },
            success: function (result) {

                // KPI cards
                var summaryMatch = result.match(/<script id="summaryData">([\s\S]*?)<\/script>/);
                if (summaryMatch) {
                    var s   = JSON.parse(summaryMatch[1]);
                    var fmt = function(n){ return parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); };
                    $('#kpiGross').text(fmt(s.gross));
                    $('#kpiPO').text(fmt(s.po_discount));
                    $('#kpiItem').text(fmt(s.item_discount));
                    $('#kpiTotalDisc').text(fmt(s.total_discount));
                    $('#kpiNet').text(fmt(s.net));
                    $('#summaryCards').css('display','flex').show();
                }

                // Invoice detail data
                var detailMatch    = result.match(/<script id="invoiceDetailData">([\s\S]*?)<\/script>/);
                var invoiceDetails = detailMatch ? JSON.parse(detailMatch[1]) : {};

                // Inject table HTML
                var html = result
                    .replace(/<script id="summaryData">[\s\S]*?<\/script>/,'')
                    .replace(/<script id="invoiceDetailData">[\s\S]*?<\/script>/,'');
                $('#targetviewdetail').html(html);

                // Destroy old DataTable
                if ($.fn.DataTable.isDataTable('#discountReportTable')) {
                    $('#discountReportTable').DataTable().destroy();
                }

                // Init DataTable
                var table = $('#discountReportTable').DataTable({
                    "paging":      true,
                    "pageLength":  25,
                    "lengthMenu":  [[10, 25, 50, 100, -1],[10, 25, 50, 100, "All"]],
                    "order":       [],
                    "autoWidth":   false,
                    "dom": "<'row'<'col-sm-5'B><'col-sm-3'l><'col-sm-4'f>>" +
                           "<'row'<'col-sm-12'tr>>" +
                           "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                    "buttons": [
                        {
                            extend: 'csv',
                            className: 'btn btn-success btn-sm',
                            title: 'Customer Discount Report',
                            text: '<i class="fas fa-file-csv mr-1"></i>CSV',
                            exportOptions: { columns: ':not(:first-child)' }
                        },
                        {
                            extend: 'pdfHtml5',
                            className: 'btn btn-danger btn-sm',
                            title: 'Customer Discount Report',
                            text: '<i class="fas fa-file-pdf mr-1"></i>PDF',
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: { columns: ':not(:first-child)' },
                            customize: function(doc) {
                                doc.defaultStyle.fontSize = 8;
                                doc.styles.tableHeader.fontSize = 9;
                                doc.styles.tableHeader.fillColor = '#343a40';
                                doc.styles.tableHeader.color = '#ffffff';
                                doc.styles.tableFooter = { fontSize:9, bold:true, fillColor:'#343a40', color:'#ffffff' };
                                doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                            }
                        },
                        {
                            extend: 'print',
                            className: 'btn btn-primary btn-sm',
                            title: 'Customer Discount Report',
                            text: '<i class="fas fa-print mr-1"></i>Print',
                            exportOptions: { columns: ':not(:first-child)' },
                            customize: function(win) {
                                $(win.document.body).find('table').css({'font-size':'10px','width':'100%'});
                            }
                        }
                    ],
                    "columnDefs": [{ "orderable": false, "targets": 0 }],
                    "drawCallback": function() {
                        this.api().columns.adjust();
                    }
                });

                // Child row toggle
                $('#discountReportTable tbody').off('click','.toggle-btn').on('click','.toggle-btn', function () {
                    var tr        = $(this).closest('tr');
                    var row       = table.row(tr);
                    var invoiceId = tr.data('invoice-id');

                    if (row.child.isShown()) {
                        row.child.hide();
                        $(this).html('&#9658;');
                    } else {
                        var items = invoiceDetails[invoiceId] || [];
                        row.child(buildChildRow(items)).show();
                        $(this).html('&#9660;');
                    }
                });
            },
            error: function () {
                $('#targetviewdetail').html("<div class='text-danger'>Error loading report. Please try again.</div>");
            }
        });
    });

});
</script>