@extends('frontend.store.theme_layout')

@section('content')
<style>
    .orders-page { display: grid; gap: 14px; padding: 30px; }
    .orders-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
    .orders-title { margin: 0; font-size: 24px; }
    .orders-sub { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
    .order-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #fff; }
    .order-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    .order-invoice { margin: 0; font-size: 16px; font-weight: 800; }
    .order-date { color: #6b7280; font-size: 12px; }
    .order-badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
    .meta-box { background: #f8fafc; border-radius: 8px; padding: 8px; }
    .meta-label { margin: 0; font-size: 11px; color: #6b7280; text-transform: uppercase; }
    .meta-value { margin: 4px 0 0; font-weight: 700; font-size: 13px; text-transform: capitalize; }
    .chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; line-height: 1; }
    .chip-new, .chip-ordered, .chip-due, .chip-pending { background: #fef3c7; color: #92400e; }
    .chip-confirmed, .chip-partial, .chip-packed { background: #dbeafe; color: #1e40af; }
    .chip-shipped { background: #e0e7ff; color: #3730a3; }
    .chip-delivered, .chip-paid, .chip-success { background: #dcfce7; color: #166534; }
    .chip-cancelled, .chip-refunded, .chip-failed { background: #fee2e2; color: #b91c1c; }
    .chip-default, .chip-type { background: #f3f4f6; color: #374151; }
    .order-actions { margin-top: 10px; display: flex; justify-content: flex-end; }
    .empty-state { text-align: center; padding: 20px; color: #6b7280; }
    @media (max-width: 900px) { .meta-grid { grid-template-columns: 1fr; } }
</style>

<div class="container orders-page">
<div class="card">
    <div class="orders-head">
        <div>
            <h2 class="orders-title">{{ __('lang_v1.my_orders') }}</h2>
            <p class="orders-sub">{{ __('storefront.orders.type_local') }} · {{ __('storefront.orders.type_partner') }}</p>
        </div>
    </div>
</div>

@forelse($orders as $entry)
    @php
        $statusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $entry->status));
        $paymentStatus = (string) ($entry->payment_status ?: 'pending');
        $shippingStatus = (string) ($entry->shipping_status ?: 'pending');
        $paymentStatusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $paymentStatus));
        $shippingStatusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $shippingStatus));
    @endphp
    <div class="order-item">
        <div class="order-top">
            <div>
                <p class="order-invoice">{{ $entry->display_id }}</p>
                <div class="order-date">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y, h:i A') }}</div>
            </div>
            <div class="order-badges">
                <span class="chip chip-type">{{ $entry->type_label }}</span>
                <span class="chip {{ in_array($statusClass, ['chip-new','chip-confirmed','chip-packed','chip-shipped','chip-delivered','chip-cancelled','chip-refunded','chip-pending','chip-success','chip-failed']) ? $statusClass : 'chip-default' }}">
                    {{ $entry->status_label }}
                </span>
            </div>
        </div>

        <div class="meta-grid">
            @if ($entry->total !== null)
                <div class="meta-box">
                    <p class="meta-label">{{ __('lang_v1.total') }}</p>
                    <p class="meta-value">{{ number_format((float) $entry->total, 2) }}</p>
                </div>
            @endif
            <div class="meta-box">
                <p class="meta-label">{{ __('lang_v1.items') }}</p>
                <p class="meta-value">{{ __('storefront.orders.items_count', ['count' => $entry->items_count]) }}</p>
                @if ($entry->entry_type === 'mixed')
                    <p class="muted" style="margin:4px 0 0;font-size:11px;">
                        {{ __('storefront.orders.local_items') }}: {{ $entry->local_items_count }}
                        · {{ __('storefront.orders.partner_items') }}: {{ $entry->partner_items_count }}
                    </p>
                @endif
            </div>
            @if ($entry->payment_status)
                <div class="meta-box">
                    <p class="meta-label">{{ __('lang_v1.payment') }}</p>
                    <p class="meta-value">
                        <span class="chip {{ in_array($paymentStatusClass, ['chip-due','chip-paid','chip-partial']) ? $paymentStatusClass : 'chip-default' }}">
                            {{ __('lang_v1.'.str_replace('_', ' ', strtolower($paymentStatus))) }}
                        </span>
                    </p>
                </div>
            @endif
            @if ($entry->shipping_status)
                <div class="meta-box">
                    <p class="meta-label">{{ __('lang_v1.shipping') }}</p>
                    <p class="meta-value">
                        <span class="chip {{ in_array($shippingStatusClass, ['chip-ordered','chip-packed','chip-shipped','chip-delivered']) ? $shippingStatusClass : 'chip-default' }}">
                            {{ str_replace('_', ' ', $shippingStatus) }}
                        </span>
                    </p>
                </div>
            @endif
            @if (! empty($entry->servo_reference))
                <div class="meta-box">
                    <p class="meta-label">{{ __('storefront.orders.partner_reference') }}</p>
                    <p class="meta-value">{{ $entry->servo_reference }}</p>
                </div>
            @endif
        </div>

        <div class="order-actions">
            <a class="btn" href="{{ $entry->detail_url }}">{{ __('lang_v1.view_details') }}</a>
        </div>
    </div>
@empty
    <div class="card empty-state">{{ __('storefront.orders.no_orders') }}</div>
@endforelse

<div class="card">
    {{ $orders->links() }}
</div>
</div>
@endsection
