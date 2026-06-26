@extends('layouts.app')

@section('title', __('lang_v1.servo_orders') . ' #' . $log->id)

@section('content')
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('lang_v1.servo_orders') #{{ $log->id }}
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.servo_order_details')])
                    <div class="row">
                        <div class="col-sm-6">
                            <p><strong>@lang('messages.date'):</strong> {{ optional($log->created_at)->format('Y-m-d H:i:s') }}</p>
                            <p><strong>@lang('sale.status'):</strong> {{ ucfirst($log->status) }}</p>
                            <p><strong>@lang('sale.customer_name'):</strong> {{ optional($log->contact)->name ?: '-' }}</p>
                            <p><strong>@lang('lang_v1.servo_client_name'):</strong> {{ $log->client_name }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p><strong>@lang('lang_v1.local_order'):</strong>
                                @if ($log->transaction_id)
                                    <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$log->transaction_id]) }}">
                                        {{ optional($log->transaction)->invoice_no ?: '#'.$log->transaction_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </p>
                            <p><strong>@lang('lang_v1.servo_reference'):</strong> {{ $log->servo_reference ?: '-' }}</p>
                            <p><strong>HTTP:</strong> {{ $log->http_status ?: '-' }}</p>
                            <p><strong>@lang('lang_v1.idempotency_key'):</strong> {{ $log->idempotency_key ?: '-' }}</p>
                        </div>
                    </div>

                    @if (!empty($log->error_message))
                        <div class="alert alert-danger tw-mt-3">
                            <strong>@lang('messages.error'):</strong> {{ $log->error_message }}
                        </div>
                    @endif

                    <h4 class="tw-mt-4">@lang('lang_v1.items')</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.product_id')</th>
                                <th>@lang('lang_v1.variation_id')</th>
                                <th>@lang('sale.qty')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($log->items ?? [] as $item)
                                <tr>
                                    <td>{{ $item['product_id'] ?? '-' }}</td>
                                    <td>{{ $item['variation_id'] ?? '-' }}</td>
                                    <td>{{ $item['quantity'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <h4 class="tw-mt-4">@lang('lang_v1.request_payload')</h4>
                    <pre class="tw-bg-gray-100 tw-p-3 tw-rounded tw-overflow-auto">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                    <h4 class="tw-mt-4">@lang('lang_v1.response_payload')</h4>
                    <pre class="tw-bg-gray-100 tw-p-3 tw-rounded tw-overflow-auto">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                    <a href="{{ action([\App\Http\Controllers\ServoOrderController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-default tw-mt-3">
                        @lang('lang_v1.go_back')
                    </a>
                @endcomponent
            </div>
        </div>
    </section>
@endsection
