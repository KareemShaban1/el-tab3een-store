{{-- Server-driven DataTable init: avoids undefined columns and wrong type when app.js is cached or #contact_type mismatches. --}}
<script type="text/javascript">
    (function () {
        var $table = $('#contact_table');
        if (!$table.length) {
            return;
        }
        if ($.fn.dataTable && typeof $.fn.dataTable.isDataTable === 'function' && $.fn.dataTable.isDataTable($table[0])) {
            return;
        }

        var contactListType = @json($type);
        var columns;

        if (contactListType === 'supplier') {
            columns = [
                { data: 'action', searchable: false, orderable: false },
                { data: 'contact_id', name: 'contact_id' },
                { data: 'supplier_business_name', name: 'supplier_business_name' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'tax_number', name: 'tax_number' },
                { data: 'pay_term', name: 'pay_term', searchable: false, orderable: false },
                { data: 'opening_balance', name: 'opening_balance', searchable: false },
                { data: 'balance', name: 'balance', searchable: false },
                { data: 'created_at', name: 'contacts.created_at' },
                { data: 'address', name: 'address', orderable: false },
                { data: 'mobile', name: 'mobile' },
                { data: 'due', searchable: false, orderable: false },
                { data: 'return_due', searchable: false, orderable: false },
            ];
        } else if (contactListType === 'customer' || contactListType === 'app_customer') {
            columns = [
                { data: 'action', searchable: false, orderable: false },
                { data: 'contact_id', name: 'contact_id' },
                { data: 'supplier_business_name', name: 'supplier_business_name' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'tax_number', name: 'tax_number' },
                { data: 'credit_limit', name: 'credit_limit' },
                { data: 'pay_term', name: 'pay_term', searchable: false, orderable: false },
                { data: 'opening_balance', name: 'opening_balance', searchable: false },
                { data: 'balance', name: 'balance', searchable: false },
                { data: 'created_at', name: 'contacts.created_at' },
            ];
            if ($('#rp_col').length) {
                columns.push({ data: 'total_rp', name: 'total_rp' });
            }
            Array.prototype.push.apply(columns, [
                { data: 'customer_group', name: 'cg.name' },
                { data: 'address', name: 'address', orderable: false },
                { data: 'mobile', name: 'mobile' },
                { data: 'due', searchable: false, orderable: false },
                { data: 'return_due', searchable: false, orderable: false },
            ]);
        } else {
            return;
        }

        window.contact_table = $table.DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            fixedHeader: false,
            scrollY: '75vh',
            scrollX: true,
            scrollCollapse: true,
            ajax: {
                url: @json(url('/contacts')),
                data: function (d) {
                    d.type = contactListType;
                    d = __datatable_ajax_callback(d);

                    if ($('#has_sell_due').length > 0 && $('#has_sell_due').is(':checked')) {
                        d.has_sell_due = true;
                    }
                    if ($('#has_sell_return').length > 0 && $('#has_sell_return').is(':checked')) {
                        d.has_sell_return = true;
                    }
                    if ($('#has_purchase_due').length > 0 && $('#has_purchase_due').is(':checked')) {
                        d.has_purchase_due = true;
                    }
                    if ($('#has_purchase_return').length > 0 && $('#has_purchase_return').is(':checked')) {
                        d.has_purchase_return = true;
                    }
                    if ($('#has_advance_balance').length > 0 && $('#has_advance_balance').is(':checked')) {
                        d.has_advance_balance = true;
                    }
                    if ($('#has_opening_balance').length > 0 && $('#has_opening_balance').is(':checked')) {
                        d.has_opening_balance = true;
                    }
                    if ($('#has_no_sell_from').length > 0) {
                        d.has_no_sell_from = $('#has_no_sell_from').val();
                    }
                    if ($('#assigned_to').length > 0) {
                        d.assigned_to = $('#assigned_to').val();
                    }
                    if ($('#cg_filter').length > 0) {
                        d.customer_group_id = $('#cg_filter').val();
                    }
                    if ($('#status_filter').length > 0) {
                        d.contact_status = $('#status_filter').val();
                    }
                },
            },
            aaSorting: [[1, 'desc']],
            columns: columns,
            fnDrawCallback: function () {
                __currency_convert_recursively($('#contact_table'));
            },
            footerCallback: function (row, data) {
                if (!data || !data.length) {
                    return;
                }
                var total_due = 0;
                var total_return_due = 0;
                for (var r = 0; r < data.length; r++) {
                    var dueCell = data[r] && data[r].due;
                    var retCell = data[r] && data[r].return_due;
                    total_due += $(dueCell).data('orig-value') ? parseFloat($(dueCell).data('orig-value')) : 0;
                    total_return_due += $(retCell).data('orig-value') ? parseFloat($(retCell).data('orig-value')) : 0;
                }
                $('.footer_contact_due').html(__currency_trans_from_en(total_due));
                $('.footer_contact_return_due').html(__currency_trans_from_en(total_return_due));
            },
        });

        $(document).on(
            'ifChanged',
            '#has_sell_due, #has_sell_return, #has_purchase_due, #has_purchase_return, #has_advance_balance, #has_opening_balance',
            function () {
                if (window.contact_table) {
                    window.contact_table.ajax.reload();
                }
            }
        );
        $(document).on('change', '#has_no_sell_from, #cg_filter, #status_filter, #assigned_to', function () {
            if (window.contact_table) {
                window.contact_table.ajax.reload();
            }
        });
    })();
</script>
