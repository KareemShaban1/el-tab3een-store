@extends('layouts.app')

@section('title', __('lang_v1.servo_orders') . ' #' . $log->id)

@section('content')
@php
    $status_labels = [
        'pending' => 'label-warning',
        'success' => 'label-success',
        'failed' => 'label-danger',
    ];
    $status_class = $status_labels[$log->status] ?? 'label-default';
    $contact = $log->contact;
    $local_transaction = $log->transaction;
    $total_items_count = count($formatted_local_items) + count($formatted_servo_items);
@endphp

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.servo_orders') #{{ $log->id }}
        @if ($is_mixed_order)
            <span class="label label-info tw-ml-2">@lang('lang_v1.mixed_order')</span>
        @endif
        <span class="label {{ $status_class }} tw-ml-2">{{ ucfirst($log->status) }}</span>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="tw-mb-3">
                <a href="{{ action([\App\Http\Controllers\ServoOrderController::class, 'index']) }}"
                    class="tw-dw-btn tw-dw-btn-default">
                    <i class="fa fa-arrow-left"></i> @lang('lang_v1.go_back')
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.servo_order_details')])
                <table class="table table-condensed tw-mb-0">
                    <tr>
                        <th style="width:40%">@lang('messages.date')</th>
                        <td>{{ @format_datetime($log->created_at) }}</td>
                    </tr>
                    @if ($has_servo_items)
                        <tr>
                            <th>@lang('sale.status') (@lang('lang_v1.order_items_servo'))</th>
                            <td><span class="label {{ $status_class }}">{{ ucfirst($log->status) }}</span></td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.servo_reference')</th>
                            <td>{{ $log->servo_reference ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.servo_client_name')</th>
                            <td>{{ $log->client_name ?: '-' }}</td>
                        </tr>
                    @endif
                    @if ($has_local_items && $local_transaction)
                        <tr>
                            <th>@lang('lang_v1.local_order')</th>
                            <td>
                                <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$local_transaction->id]) }}">
                                    {{ $local_transaction->invoice_no ?: '#'.$local_transaction->id }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('sale.status') (@lang('lang_v1.order_items_tab3een'))</th>
                            <td>{{ ucfirst($local_transaction->status) }}</td>
                        </tr>
                        @if (! empty($local_transaction->payment_status))
                            <tr>
                                <th>@lang('lang_v1.payment')</th>
                                <td>{{ ucfirst($local_transaction->payment_status) }}</td>
                            </tr>
                        @endif
                        @if (! empty($local_transaction->shipping_status))
                            <tr>
                                <th>@lang('lang_v1.shipping')</th>
                                <td>{{ ucfirst($local_transaction->shipping_status) }}</td>
                            </tr>
                        @endif
                    @elseif (! $has_local_items)
                        <tr>
                            <th>@lang('lang_v1.local_order')</th>
                            <td>-</td>
                        </tr>
                    @endif
                    <tr>
                        <th>@lang('lang_v1.total_items')</th>
                        <td>{{ $total_items_count }}</td>
                    </tr>
                    @if ($grand_total > 0)
                        <tr>
                            <th>@lang('lang_v1.order_total')</th>
                            <td>@format_currency($grand_total)</td>
                        </tr>
                    @endif
                </table>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('contact.contact_info', ['contact' => __('role.customer')])])
                @if ($contact)
                    <table class="table table-condensed tw-mb-0">
                        <tr>
                            <th style="width:40%">@lang('contact.name')</th>
                            <td>
                                <a href="#" class="btn-modal"
                                    data-href="{{ action([\App\Http\Controllers\ServoOrderController::class, 'clientDetails'], [$contact->id]) }}"
                                    data-container=".view_modal">
                                    {{ $contact->name }}
                                </a>
                                @can('customer.view')
                                    <a href="{{ action([\App\Http\Controllers\ContactController::class, 'show'], [$contact->id]) }}"
                                        class="tw-ml-2" target="_blank" title="@lang('messages.view')">
                                        <i class="fa fa-external-link-alt"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('contact.mobile')</th>
                            <td>
                                @if (! empty($contact->mobile))
                                    <a href="tel:{{ $contact->mobile }}">{{ $contact->mobile }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @if (! empty($contact->landline))
                            <tr>
                                <th>@lang('contact.landline')</th>
                                <td><a href="tel:{{ $contact->landline }}">{{ $contact->landline }}</a></td>
                            </tr>
                        @endif
                        <tr>
                            <th>@lang('business.email')</th>
                            <td>
                                @if (! empty($contact->email))
                                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @if (! empty($contact->contact_address))
                            <tr>
                                <th>@lang('business.address')</th>
                                <td>{!! $contact->contact_address !!}</td>
                            </tr>
                        @endif
                    </table>
                @else
                    <p class="text-muted tw-mb-0">-</p>
                @endif
            @endcomponent
        </div>
    </div>

    @if (!empty($log->error_message))
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    <strong>@lang('messages.error'):</strong> {{ $log->error_message }}
                </div>
            </div>
        </div>
    @endif

    @if ($has_local_items)
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.order_items_tab3een')])
                    @include('servo_orders.partials.items_table', [
                        'items' => $formatted_local_items,
                        'section_total' => $local_total,
                    ])
                @endcomponent
            </div>
        </div>
    @endif

    @if ($has_servo_items)
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.order_items_servo')])
                    @include('servo_orders.partials.items_table', [
                        'items' => $formatted_servo_items,
                        'section_total' => $servo_total,
                    ])
                @endcomponent
            </div>
        </div>
    @endif

    @if ($is_mixed_order && $grand_total > 0)
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-default', 'title' => __('lang_v1.order_total')])
                    <p class="tw-mb-0 tw-text-lg tw-font-semibold">
                        @format_currency($grand_total)
                    </p>
                @endcomponent
            </div>
        </div>
    @endif

    @if (! empty($log->request_payload) || ! empty($log->response_payload) || ! empty($log->idempotency_key))
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-default', 'title' => __('lang_v1.technical_details')])
                    <details>
                        <summary class="tw-cursor-pointer tw-text-primary tw-mb-3">@lang('messages.view') @lang('lang_v1.technical_details')</summary>
                        <div class="tw-space-y-4">
                            @if (! empty($log->idempotency_key))
                                <p class="tw-mb-2">
                                    <strong>@lang('lang_v1.idempotency_key'):</strong>
                                    <code>{{ $log->idempotency_key }}</code>
                                </p>
                            @endif
                            @if (! empty($log->http_status))
                                <p class="tw-mb-2">
                                    <strong>HTTP:</strong> {{ $log->http_status }}
                                </p>
                            @endif
                            @if (! empty($log->request_payload))
                                <div>
                                    <strong>@lang('lang_v1.request_payload')</strong>
                                    <pre class="tw-bg-gray-100 tw-p-3 tw-rounded tw-overflow-auto tw-mt-2 tw-mb-0">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                            @if (! empty($log->response_payload))
                                <div>
                                    <strong>@lang('lang_v1.response_payload')</strong>
                                    <pre class="tw-bg-gray-100 tw-p-3 tw-rounded tw-overflow-auto tw-mt-2 tw-mb-0">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        </div>
                    </details>
                @endcomponent
            </div>
        </div>
    @endif
</section>
@endsection
