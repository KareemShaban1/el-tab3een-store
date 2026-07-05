@extends('layouts.app')

@section('title', __('lang_v1.profit_by_user'))

@section('content')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.profit_by_user')
        <small><a href="{{ action([\App\Http\Controllers\SellController::class, 'index']) }}">@lang('lang_v1.all_sales')</a></small>
    </h1>
</section>

<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_location_id', 'sell_list_filter_date_range', 'created_by']])
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.profit_by_user')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-text-center" id="sell_user_profit_table">
                <thead>
                    <tr>
                        <th>@lang('report.user')</th>
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
                sell_user_profit_table.ajax.reload();
            }
        );
        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function() {
            $(this).val('');
            sell_user_profit_table.ajax.reload();
        });

        sell_user_profit_table = $('#sell_user_profit_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: '/reports/get-profit/user',
                data: function(d) {
                    if ($('#sell_list_filter_date_range').val()) {
                        d.start_date = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.end_date = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    d.location_id = $('#sell_list_filter_location_id').val();
                    d.created_by = $('#created_by').val();
                }
            },
            columns: [
                { data: 'user_name', name: 'user_name' },
                { data: 'gross_profit', searchable: false }
            ],
            footerCallback: function(row, data) {
                var total_profit = 0;
                for (var r in data) {
                    total_profit += $(data[r].gross_profit).data('orig-value')
                        ? parseFloat($(data[r].gross_profit).data('orig-value'))
                        : 0;
                }
                $('#sell_user_profit_table .footer_total').html(__currency_trans_from_en(total_profit));
            }
        });

        $('#sell_list_filter_location_id, #created_by').change(function() {
            sell_user_profit_table.ajax.reload();
        });
    });
</script>
@endsection
