<?php 
include "include/header.php";  
include "include/topnavbar.php"; 

$sqlarea="SELECT `idtbl_area`, `area` FROM `tbl_area` WHERE `status`=1 ORDER BY `area` ASC";
$resultarea = $conn->query($sqlarea);

$sqlrep="SELECT `idtbl_employee`, `name` FROM `tbl_employee` WHERE `status`=1 ORDER BY `name` ASC";
$resultrep = $conn->query($sqlrep);
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
                            <div class="page-header-icon"><i data-feather="bar-chart-2"></i></div>
                            <span>Item Sale by Rep Report</span>
                        </h1>
                    </div>
                </div>
            </div>

            <div class="container-fluid mt-2 p-0 p-2">
                <div class="card">
                    <div class="card-body p-0 p-2">
                        <div class="row">
                            <div class="col-12">
                                <form id="saleInformationForm">
                                    <div class="form-row">

                                        <!-- Product - Select2 AJAX search -->
                                        <div class="col-2">
                                            <label class="small font-weight-bold text-dark">Product*</label>
                                            <select class="form-control form-control-sm" name="selectProduct" id="selectProduct">
                                                <option value="0">-- Select Product --</option>
                                            </select>
                                        </div>

                                        <!-- Rep (optional filter) -->
                                        <div class="col-2">
                                            <label class="small font-weight-bold text-dark">Rep</label>
                                            <select class="form-control form-control-sm" name="selectSaleRep" id="selectSaleRep">
                                                <option value="0">All Reps</option>
                                                <?php while ($rowresultrep = $resultrep->fetch_assoc()) { ?>
                                                <option value="<?php echo $rowresultrep['idtbl_employee']; ?>">
                                                    <?php echo $rowresultrep['name']; ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <!-- Area (optional filter) -->
                                        <div class="col-2">
                                            <label class="small font-weight-bold text-dark">Area</label>
                                            <select class="form-control form-control-sm" name="selectArea" id="selectArea">
                                                <option value="0">All Areas</option>
                                                <?php while ($rowarealist = $resultarea->fetch_assoc()) { ?>
                                                <option value="<?php echo $rowarealist['idtbl_area']; ?>">
                                                    <?php echo $rowarealist['area']; ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <!-- Date From -->
                                        <div class="col-2">
                                            <label class="small font-weight-bold text-dark">From*</label>
                                            <input type="date" class="form-control form-control-sm" name="fromdate" id="fromdate" required>
                                        </div>

                                        <!-- Date To -->
                                        <div class="col-2">
                                            <label class="small font-weight-bold text-dark">To*</label>
                                            <input type="date" class="form-control form-control-sm" name="todate" id="todate" required>
                                        </div>

                                        <!-- Submit -->
                                        <div class="col-2 d-flex align-items-end">
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-sm w-100 btnPdf"
                                                id="submitBtn">
                                                &nbsp;View
                                            </button>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>

                        <hr>

                        <div class="col-12">
                            <hr class="border-dark">
                            <div id="targetviewdetail">
                                <!-- Results rendered here -->
                            </div>
                        </div>

                        <br>

                        <div class="col-12" style="display: none" align="right" id="hideprintBtn">
                            <button type="button"
                                class="btn btn-outline-danger btn-sm ml-auto w-10 mt-2 px-5 align-right printBtn"
                                id="printBtn">
                                <i class="fas fa-file-pdf"></i>&nbsp;Print
                            </button>
                        </div>

                        <div class="col-12" id="showpdfview" style="display: none;">
                            <div class="embed-responsive embed-responsive-1by1" id="pdfframe">
                                <iframe class="embed-responsive-item" frameborder="0"></iframe>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
        <?php include "include/footerbar.php"; ?>
    </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printreport" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">Item Sale by Rep Report PDF</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="embed-responsive embed-responsive-16by9" id="frame">
                            <iframe class="embed-responsive-item" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "include/footerscripts.php"; ?>
<!-- Select2 for searchable product dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    /* ── Select2: match Bootstrap form-control-sm ── */
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
    .select2-container--default .select2-results__option {
        font-size: 0.875rem !important;
        padding: 4px 8px !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        font-size: 0.875rem !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.2rem !important;
        padding: 3px 6px !important;
    }
    .select2-dropdown {
        border: 1px solid #ced4da !important;
        border-radius: 0.2rem !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #005EB8 !important;
    }
    .select2-container {
        width: 100% !important;
    }

    /* Force DataTable and all wrappers to full width */
    #targetviewdetail,
    #targetviewdetail .dataTables_wrapper,
    #targetviewdetail .dataTables_scroll,
    #targetviewdetail .dataTables_scrollBody,
    #targetviewdetail table,
    #targetviewdetail table.dataTable,
    #reportTable {
        width: 100% !important;
        min-width: 100% !important;
        box-sizing: border-box;
    }
    /* Keep tfoot styled like the PHP-rendered dark row */
    #reportTable tfoot tr {
        background-color: #343a40;
        color: #ffffff;
    }
    /* Make all columns share width equally */
    #reportTable th,
    #reportTable td {
        white-space: nowrap;
    }
</style>
<script>
$(document).ready(function () {

    /* ── Searchable product dropdown — server-side AJAX ── */
    $('#selectProduct').select2({
        placeholder: '-- Select Product --',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,   // show results immediately on open
        ajax: {
            url: 'getprocess/searchproduct.php',
            dataType: 'json',
            delay: 250,           // debounce: wait 250ms after typing stops
            cache: true,
            data: function (params) {
                return {
                    q:    params.term || '',   // search term
                    page: params.page  || 1   // pagination page
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results:    data.results,
                    pagination: data.pagination
                };
            }
        }
    });

    /* ── Form submit ── */
    $('#saleInformationForm').submit(function (event) {
        event.preventDefault();

        var product  = $('#selectProduct').val() || 0;  // Select2 returns null when cleared
        var rep      = $('#selectSaleRep').val();
        var area     = $('#selectArea').val();
        var fromdate = $('#fromdate').val();
        var todate   = $('#todate').val();

        if (!fromdate || !todate) {
            alert('Please select a date range.');
            return;
        }

        $.ajax({
            type: "POST",
            data: {
                product:   product,
                rep:       rep,
                area:      area,
                validfrom: fromdate,
                validto:   todate
            },
            url: 'getprocess/getitemsalerepwise.php',
            success: function (result) {
                $('#targetviewdetail').html(result);
                $('#hideprintBtn').show();

                if ($.fn.DataTable.isDataTable('#reportTable')) {
                    $('#reportTable').DataTable().destroy();
                }

                $('#reportTable').DataTable({
                    "paging": false,
                    "order": [],
                    "scrollX": false,
                    "autoWidth": true,
                    "dom": "<'row'<'col-sm-5'B><'col-sm-2'l><'col-sm-5'f>>" +
                           "<'row'<'col-sm-12'tr>>" +
                           "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                    "buttons": [
                        {
                            extend: 'csv',
                            className: 'btn btn-success btn-sm',
                            title: 'Item Sale by Rep Report',
                            text: '<i class="fas fa-file-csv mr-2"></i> CSV'
                        },
                        {
                            extend: 'pdfHtml5',
                            className: 'btn btn-danger btn-sm',
                            title: 'Item Sale by Rep Report',
                            text: '<i class="fas fa-file-pdf mr-2"></i> PDF',
                            orientation: 'portrait',
                            pageSize: 'A4',
                            exportOptions: { columns: ':visible' },
                            customize: function(doc) {
                                doc.defaultStyle.fontSize = 9;
                                doc.styles.tableHeader.fontSize = 10;
                                doc.styles.tableHeader.fillColor = '#343a40';
                                doc.styles.tableHeader.color = '#ffffff';
                                doc.styles.tableFooter = {
                                    fontSize: 10, bold: true,
                                    fillColor: '#343a40', color: '#ffffff'
                                };
                                doc.content[1].table.widths =
                                    Array(doc.content[1].table.body[0].length + 1)
                                        .join('*').split('');
                            }
                        },
                        {
                            extend: 'print',
                            title: 'Item Sale by Rep Report',
                            className: 'btn btn-primary btn-sm',
                            text: '<i class="fas fa-print mr-2"></i> Print',
                            customize: function(win) {
                                $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', '11px')
                                    .css('width', '100%');
                                $(win.document.body).find('h1').css('font-size', '16px');
                            }
                        }
                    ],
                    "initComplete": function() {
                        var api = this.api();
                        $(api.table().node()).css('width', '100%');
                        $(api.table().container()).css('width', '100%');
                        api.columns.adjust();
                    },
                    "drawCallback": function() {
                        var api = this.api();
                        $(api.table().node()).css('width', '100%');
                        api.columns.adjust();
                    }
                });
            }
        });
    });

    /* ── Print PDF button ── */
    $('#printBtn').click(function () {
        var product  = encodeURIComponent($('#selectProduct').val() || 0);
        var rep      = encodeURIComponent($('#selectSaleRep').val());
        var area     = encodeURIComponent($('#selectArea').val());
        var fromdate = encodeURIComponent($('#fromdate').val());
        var todate   = encodeURIComponent($('#todate').val());

        $('#frame').html('<iframe class="embed-responsive-item" frameborder="0"></iframe>');
        $('#printreport iframe').contents().find('body').html(
            "<img src='images/spinner.gif' class='img-fluid' style='margin-top:200px;margin-left:500px;' />"
        );

        var params = '?validfrom=' + fromdate + '&validto=' + todate +
                     '&product=' + product + '&rep=' + rep + '&area=' + area;
        var src = 'pdfprocess/itemsalerepwisepdf.php' + params;

        $('#printreport iframe').attr({
            'src': src,
            'height': 640,
            'width': 960,
            'allowfullscreen': ''
        });

        $('#printreport').modal({ keyboard: false, backdrop: 'static' });
    });

});
</script>