@extends('frontend.store.theme_layout')

@section('content')
<style>
    .order-page {
        display: grid;
        gap: 16px;
padding: 30px;
    }
    .order-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .order-id {
        margin: 0;
        font-size: 24px;
    }
    .order-sub {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .chip-new, .chip-ordered, .chip-due { background: #fef3c7; color: #92400e; }
    .chip-confirmed, .chip-partial, .chip-packed { background: #dbeafe; color: #1e40af; }
    .chip-shipped { background: #e0e7ff; color: #3730a3; }
    .chip-delivered, .chip-paid { background: #dcfce7; color: #166534; }
    .chip-cancelled, .chip-refunded { background: #fee2e2; color: #b91c1c; }
    .chip-default { background: #f3f4f6; color: #374151; }
    .order-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .metric-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        background: #fff;
    }
    .metric-label {
        margin: 0;
        font-size: 12px;
        color: #6b7280;
    }
    .metric-value {
        margin: 8px 0 0;
        font-size: 18px;
        font-weight: 800;
    }
    .section-title {
        margin: 0 0 10px;
        font-size: 18px;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table th, .items-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
        vertical-align: top;
    }
    .items-table th {
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .txt-right {
        text-align: right !important;
    }
    .muted {
        color: #6b7280;
        font-size: 12px;
    }
    .card-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-soft {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 12px;
        color: #111827;
        text-decoration: none;
        font-weight: 600;
        background: #fff;
    }
    .btn-soft:hover { background: #f8fafc; }
    @media (max-width: 900px) {
        .order-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    $orderStatus = (string) ($order->ecommerce_order_status ?: $order->sub_status ?: 'new');
    $paymentStatus = (string) ($order->payment_status ?: 'pending');
    $shippingStatus = (string) ($order->shipping_status ?: 'pending');
    $statusClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $orderStatus));
    $paymentClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $paymentStatus));
    $shippingClass = 'chip-' . strtolower(str_replace([' ', '_'], '-', $shippingStatus));
    $shippingAddress = $order->shipping_address(true);
@endphp

<div class="container order-page">
    <div class="card">
        <div class="order-header">
            <div>
                <h2 class="order-id">Order #{{ $order->invoice_no ?: $order->id }}</h2>
                <p class="order-sub">Placed on {{ \Carbon\Carbon::parse($order->transaction_date)->format('d M Y, h:i A') }}</p>
            </div>
            <span class="chip {{ in_array($statusClass, ['chip-new','chip-confirmed','chip-packed','chip-shipped','chip-delivered','chip-cancelled','chip-refunded']) ? $statusClass : 'chip-default' }}">
                {{ str_replace('_', ' ', $orderStatus) }}
            </span>
        </div>
    </div>

    <div class="order-grid">
        <div class="metric-card">
            <p class="metric-label">Total Amount</p>
            <p class="metric-value">{{ number_format((float) $order->final_total, 2) }}</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Payment</p>
            <p class="metric-value">
                <span class="chip {{ in_array($paymentClass, ['chip-due','chip-paid','chip-partial']) ? $paymentClass : 'chip-default' }}">
                    {{ str_replace('_', ' ', $paymentStatus) }}
                </span>
            </p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Shipping</p>
            <p class="metric-value">
                <span class="chip {{ in_array($shippingClass, ['chip-ordered','chip-packed','chip-shipped','chip-delivered']) ? $shippingClass : 'chip-default' }}">
                    {{ str_replace('_', ' ', $shippingStatus) }}
                </span>
            </p>
        </div>
    </div>

    <div class="card">
        <h3 class="section-title">Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="txt-right">Qty</th>
                    <th class="txt-right">Unit Price</th>
                    <th class="txt-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->sell_lines as $line)
                    @php
                        $lineQty = (float) $line->quantity;
                        $linePrice = (float) $line->unit_price_inc_tax;
                        $itemName = optional($line->product)->name ?: 'Product #' . $line->product_id;
                        $variantName = optional($line->variations)->name ?: ('Variation #' . $line->variation_id);
                    @endphp
                    <tr>
                        <td>
                            <div><strong>{{ $itemName }}</strong></div>
                            <div class="muted">{{ $variantName }}</div>
                        </td>
                        <td class="txt-right">{{ number_format($lineQty, 2) }}</td>
                        <td class="txt-right">{{ number_format($linePrice, 2) }}</td>
                        <td class="txt-right">{{ number_format($lineQty * $linePrice, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="txt-right"><strong>Grand Total</strong></td>
                    <td class="txt-right"><strong>{{ number_format((float) $order->final_total, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
</div>

<div class="card">
    <h3 class="section-title">Shipping Details</h3>
    @if(!empty($shippingAddress))
        <div><strong>Name:</strong> {{ $shippingAddress['name'] ?? '-' }}</div>
        <div><strong>Mobile:</strong> {{ data_get($order->order_addresses ? json_decode($order->order_addresses, true) : [], 'shipping_address.shipping_mobile', '-') }}</div>
        <div><strong>Address:</strong> {{ implode(', ', array_filter([
            $shippingAddress['address_line_1'] ?? null,
            $shippingAddress['address_line_2'] ?? null,
            $shippingAddress['city'] ?? null,
            $shippingAddress['state'] ?? null,
            $shippingAddress['country'] ?? null,
            $shippingAddress['zipcode'] ?? null,
        ])) ?: '-' }}</div>
    @else
        <div class="muted">No shipping details found for this order.</div>
    @endif
</div>

    <div class="card">
        <div class="card-actions">
            <a href="{{ route('store.account.orders') }}" class="btn-soft">Back to orders</a>
            <a href="{{ route('store.products.index') }}" class="btn-soft">Continue shopping</a>
        </div>
    </div>
</div>
@endsection

