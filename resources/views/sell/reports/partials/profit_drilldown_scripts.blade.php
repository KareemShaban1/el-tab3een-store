<script type="text/javascript">
    function getProfitReportFilterParams() {
        var params = {};
        if ($('#sell_list_filter_date_range').val() && $('#sell_list_filter_date_range').data('daterangepicker')) {
            params.start_date = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        var locationId = $('#sell_list_filter_location_id').val();
        if (locationId) {
            params.location_id = locationId;
        }
        return params;
    }

    function initProfitPersonDetailsTable() {
        var tableEl = $('#profit_person_details_table');
        if (!tableEl.length) {
            return;
        }

        var type = tableEl.data('type');
        var userId = tableEl.data('user-id');
        var filterParams = getProfitReportFilterParams();

        if ($.fn.DataTable.isDataTable(tableEl)) {
            tableEl.DataTable().destroy();
        }

        tableEl.DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: '/reports/profit-by-person-details',
                data: function(d) {
                    d.type = type;
                    d.user_id = userId;
                    if (filterParams.start_date) {
                        d.start_date = filterParams.start_date;
                    }
                    if (filterParams.end_date) {
                        d.end_date = filterParams.end_date;
                    }
                    if (filterParams.location_id) {
                        d.location_id = filterParams.location_id;
                    }
                }
            },
            columns: [
                { data: 'transaction_date', name: 'sale.transaction_date' },
                { data: 'invoice_no', name: 'sale.invoice_no' },
                { data: 'product', name: 'product' },
                { data: 'quantity', name: 'quantity', searchable: false },
                { data: 'unit_price_inc_tax', name: 'transaction_sell_lines.unit_price_inc_tax', searchable: false },
                { data: 'purchase_total', name: 'purchase_total', searchable: false },
                { data: 'sell_total', name: 'sell_total', searchable: false },
                { data: 'line_profit', name: 'line_profit', searchable: false }
            ],
            fnDrawCallback: function() {
                var purchaseTotal = sum_table_col($('#profit_person_details_table'), 'purchase_total');
                var sellTotal = sum_table_col($('#profit_person_details_table'), 'sell_total');
                var lineProfit = sum_table_col($('#profit_person_details_table'), 'line_profit');
                $('#profit_person_details_table .footer_purchase_total').html(
                    __currency_trans_from_en(purchaseTotal, true)
                );
                $('#profit_person_details_table .footer_sell_total').html(
                    __currency_trans_from_en(sellTotal, true)
                );
                $('#profit_person_details_table .footer_line_profit').html(
                    __currency_trans_from_en(lineProfit, true)
                );
            }
        });
    }

    function openProfitDetailsModal(type, userId) {
        var params = $.extend({
            type: type,
            user_id: userId
        }, getProfitReportFilterParams());

        var container = $('.view_modal');
        container.empty();
        $.get('/reports/profit-by-person-details?' + $.param(params), function(html) {
            container.html(html);
            container.modal('show');
            initProfitPersonDetailsTable();
        });
    }

    $(document).on('click', '.profit-drilldown', function(e) {
        e.preventDefault();
        openProfitDetailsModal($(this).data('type'), $(this).data('user-id'));
    });
</script>
