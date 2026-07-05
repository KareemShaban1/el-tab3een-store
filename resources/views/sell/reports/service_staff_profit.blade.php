@extends('layouts.app')

@section('title', __('lang_v1.profit_by_service_staff'))

@section('content')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.profit_by_service_staff')
        <small><a href="{{ action([\App\Http\Controllers\SellController::class, 'index']) }}">@lang('lang_v1.all_sales')</a></small>
    </h1>
</section>

<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
            </div>
        </div>
        @if(!empty($service_staffs))
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('service_staff_id', __('restaurant.service_staff') . ':') !!}
                {!! Form::select('service_staff_id', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        @endif
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.profit_by_service_staff')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-text-center" id="sell_service_staff_profit_table">
                <thead>
                    <tr>
                        <th>@lang('restaurant.service_staff')</th>
                        <th>@lang('lang_v1.gross_profit')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="bg-gray font-17 footer-total">
                        <td><strong>@lang('sale.total'):</strong></td>
                        <td class="footer_total"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
@include('sell.reports.partials.profit_drilldown_scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#sell_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                sell_service_staff_profit_table.ajax.reload();
            }
        );
        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function() {
            $(this).val('');
            sell_service_staff_profit_table.ajax.reload();
        });

        sell_service_staff_profit_table = $('#sell_service_staff_profit_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: '/reports/get-profit/service_staff',
                data: function(d) {
                    if ($('#sell_list_filter_date_range').val()) {
                        d.start_date = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.end_date = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    d.location_id = $('#sell_list_filter_location_id').val();
                    d.service_staff_id = $('#service_staff_id').val();
                }
            },
            columns: [
                { data: 'staff_name', name: 'staff_name' },
                { data: 'gross_profit', searchable: false }
            ],
            footerCallback: function(row, data) {
                var total_profit = 0;
                for (var r in data) {
                    total_profit += $(data[r].gross_profit).data('orig-value')
                        ? parseFloat($(data[r].gross_profit).data('orig-value'))
                        : 0;
                }
                $('#sell_service_staff_profit_table .footer_total').html(__currency_trans_from_en(total_profit));
            }
        });

        $('#sell_list_filter_location_id, #service_staff_id').change(function() {
            sell_service_staff_profit_table.ajax.reload();
        });
    });
</script>
@endsection
