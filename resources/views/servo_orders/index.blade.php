@extends('layouts.app')

@section('title', __('lang_v1.servo_orders'))

@section('content')
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.servo_orders')</h1>
    </section>

    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('status_filter', __('sale.status') . ':') !!}
                    {!! Form::select('status_filter', [
                        '' => __('lang_v1.all'),
                        'pending' => __('lang_v1.pending'),
                        'success' => __('lang_v1.success'),
                        'failed' => __('lang_v1.failed'),
                    ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'status_filter']) !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.servo_orders')])
            <table class="table table-bordered table-striped" id="servo_orders_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('lang_v1.servo_client_name')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('lang_v1.total_items')</th>
                        <th>@lang('lang_v1.local_order')</th>
                        <th>@lang('lang_v1.servo_reference')</th>
                        <th>HTTP</th>
                    </tr>
                </thead>
            </table>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            const table = $('#servo_orders_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                ajax: {
                    url: '{{ action([\App\Http\Controllers\ServoOrderController::class, 'index']) }}',
                    data: function(d) {
                        d.status = $('#status_filter').val();
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'servo_order_logs.created_at'
                    },
                    {
                        data: 'customer_name',
                        name: 'contacts.name'
                    },
                    {
                        data: 'client_name',
                        name: 'servo_order_logs.client_name'
                    },
                    {
                        data: 'status',
                        name: 'servo_order_logs.status'
                    },
                    {
                        data: 'items_count',
                        name: 'items_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'local_order',
                        name: 'local_order',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'servo_reference',
                        name: 'servo_order_logs.servo_reference'
                    },
                    {
                        data: 'http_status',
                        name: 'servo_order_logs.http_status'
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            $('#status_filter').change(function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
