@extends('frontend.store.theme_layout')

@section('content')
<style>
    .orders-page { display: grid; gap: 14px; padding: 30px; }
    .orders-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
    .orders-title { margin: 0; font-size: 24px; }
    .orders-sub { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
    .orders-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .order-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #fff; }
    .order-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    .order-invoice { margin: 0; font-size: 16px; font-weight: 800; }
    .order-date { color: #6b7280; font-size: 12px; }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
    .meta-box { background: #f8fafc; border-radius: 8px; padding: 8px; }
    .meta-label { margin: 0; font-size: 11px; color: #6b7280; text-transform: uppercase; }
    .meta-value { margin: 4px 0 0; font-weight: 700; font-size: 13px; text-transform: capitalize; }
    .chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; line-height: 1; }
    .chip-new, .chip-ordered, .chip-due { background: #fef3c7; color: #92400e; }
    .chip-confirmed, .chip-partial, .chip-packed { background: #dbeafe; color: #1e40af; }
    .chip-shipped { background: #e0e7ff; color: #3730a3; }
    .chip-delivered, .chip-paid { background: #dcfce7; color: #166534; }
    .chip-cancelled, .chip-refunded { background: #fee2e2; color: #b91c1c; }
    .chip-default { background: #f3f4f6; color: #374151; }
    .order-actions { margin-top: 10px; display: flex; justify-content: flex-end; }
    .empty-state { text-align: center; padding: 20px; color: #6b7280; }
    @media (max-width: 900px) { .orders-grid { grid-template-columns: 1fr; } }
</style>

<div class="container orders-page">
<div class="card">
    <div class="orders-head">
        <div>
            <h2 class="orders-title"> {{ __('lang_v1.my_orders') }}</h2>
            <p class="orders-sub"> {{ __('lang_v1.track_your_ecommerce_orders_shipping_and_payment_status') }}</p>
        </div>
    </div>
</div>

@forelse($orders as $order)
    @php
        $orderStatus = (string) ($order->ecommerce_order_status ?: $order->sub_status ?: 'new');
        if (strpos($orderStatus, 'ecommerce_') === 0) {
            $orderStatus = substr($orderStatus, 10);
        }
        $paymentStatus = (string) ($order->payment_status ?: 'pending');
        $shippingStatus = (string) ($order->shipping_status ?: 'pending');
        $orderStatusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $orderStatus));
        $paymentStatusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $paymentStatus));
        $shippingStatusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $shippingStatus));
    @endphp
    <div class="order-item">
        <div class="order-top">
            <div>
                <p class="order-invoice">{{ $order->invoice_no ?: '#'.$order->id }}</p>
                <div class="order-date">{{ \Carbon\Carbon::parse($order->transaction_date)->format('d M Y, h:i A') }}</div>
            </div>
            <span class="chip {{ in_array($orderStatusClass, ['chip-new','chip-confirmed','chip-packed','chip-shipped','chip-delivered','chip-cancelled','chip-refunded']) ? $orderStatusClass : 'chip-default' }}">
                {{ __('lang_v1.'.str_replace('_', ' ', strtolower($orderStatus))) }}
            </span>
        </div>

        <div class="meta-grid">
            <div class="meta-box">
                <p class="meta-label"> {{ __('lang_v1.total') }}</p>
                <p class="meta-value">{{ number_format((float)$order->final_total, 2) }}</p>
            </div>
            <div class="meta-box">
                <p class="meta-label"> {{ __('lang_v1.payment') }}</p>
                <p class="meta-value">
                    <span class="chip {{ in_array($paymentStatusClass, ['chip-due','chip-paid','chip-partial']) ? $paymentStatusClass : 'chip-default' }}">{{ __('lang_v1.'.str_replace('_', ' ', strtolower($paymentStatus))) }}</span>
                </p>
            </div>
            <div class="meta-box">
                <p class="meta-label"> {{ __('lang_v1.shipping') }}</p>
                <p class="meta-value">
                    <span class="chip {{ in_array($shippingStatusClass, ['chip-ordered','chip-packed','chip-shipped','chip-delivered']) ? $shippingStatusClass : 'chip-default' }}">{{ str_replace('_', ' ', $shippingStatus) }}</span>
                </p>
            </div>
            <div class="meta-box">
                <p class="meta-label"> {{ __('lang_v1.order_id') }}</p>
                <p class="meta-value">#{{ $order->id }}</p>
            </div>
        </div>

        <div class="order-actions">
            <a class="btn" href="{{ route('store.account.orders.show', $order->id) }}"> {{ __('lang_v1.view_details') }}</a>
        </div>
    </div>
@empty
    <div class="card empty-state"> {{ __('lang_v1.you_do_not_have_any_ecommerce_orders_yet') }}</div>
@endforelse

<div class="card">
    {{ $orders->links() }}
</div>
</div>
@endsection

