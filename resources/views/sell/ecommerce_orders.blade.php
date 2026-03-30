@extends('layouts.app')
@php
    $is_ecommerce_orders = true;
    $page_title = 'E-commerce Orders';
@endphp

@section('title', $page_title)

@section('content')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ $page_title }}
            <span id="sell_list_selected_range" class="tw-text-gray-600 tw-font-normal tw-text-base">
                {{ @format_date(\Carbon\Carbon::now()->subDays(29)) }} ~ {{ @format_date(\Carbon\Carbon::now()) }}
            </span>
        </h1>
    </section>

    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('sell.partials.sell_list_filters')
            @if ($payment_types)
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                        {!! Form::select('payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ecommerce_order_status', 'E-commerce Order Status:') !!}
                    {!! Form::select('ecommerce_order_status', $ecommerce_order_statuses ?? [], null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => $page_title])
            @if (auth()->user()->can('direct_sell.view') ||
                    auth()->user()->can('view_own_sell_only') ||
                    auth()->user()->can('view_commission_agent_sell'))
                <table class="table table-bordered table-striped ajax_view" id="sell_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>E-commerce Order Status</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>Show</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            @endif
        @endcomponent
    </section>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <section class="invoice print_section" id="receipt_section"></section>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            var startLast30 = moment().subtract(29, 'days');
            var endLast = moment();

            function updateDateRangeHeading(start, end) {
                if (start && end) {
                    var formattedStart = start.format(moment_date_format);
                    var formattedEnd = end.format(moment_date_format);
                    $('#sell_list_selected_range').text(formattedStart + ' ~ ' + formattedEnd);
                } else {
                    var defaultStart = moment().subtract(29, 'days').format(moment_date_format);
                    var defaultEnd = moment().format(moment_date_format);
                    $('#sell_list_selected_range').text(defaultStart + ' ~ ' + defaultEnd);
                }
            }

            $('#sell_list_filter_date_range').daterangepicker(
                $.extend(true, {}, dateRangeSettings, {
                    startDate: startLast30,
                    endDate: endLast
                }),
                function(start, end) {
                    updateDateRangeHeading(start, end);
                    sell_table.ajax.reload();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function() {
                $('#sell_list_filter_date_range').val('');
                updateDateRangeHeading(null, null);
                sell_table.ajax.reload();
            });

            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc']
                ],
                ajax: {
                    url: "{{ route('sells.ecommerce.orders.data') }}",
                    data: function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.is_direct_sale = 1;
                        d.source = 'ecommerce';
                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();
                        d.shipping_status = $('#shipping_status').val();
                        d.payment_method = $('#payment_method').val();
                        d.ecommerce_order_status = $('#ecommerce_order_status').val();
                        d = __datatable_ajax_callback(d);
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    {
                        data: 'ecommerce_order_status_label',
                        name: 'transactions.ecommerce_order_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_items',
                        name: 'total_items',
                        searchable: false
                    },
                    {
                        data: 'view_order',
                        name: 'view_order',
                        orderable: false,
                        searchable: false
                    },
                ],
                fnDrawCallback: function() {
                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function(row) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change',
                '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #payment_method, #ecommerce_order_status',
                function() {
                    sell_table.ajax.reload();
                });

            $('#only_subscriptions').on('ifChanged', function() {
                sell_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection













