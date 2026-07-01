@extends('frontend.store.theme_layout')

@section('content')
<style>
    .order-page { display: grid; gap: 16px; padding: 30px; }
    .order-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .order-id { margin: 0; font-size: 24px; }
    .order-sub { margin: 6px 0 0; color: #6b7280; font-size: 14px; }
    .chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; line-height: 1; white-space: nowrap; }
    .chip-pending { background: #fef3c7; color: #92400e; }
    .chip-success { background: #dcfce7; color: #166534; }
    .chip-failed { background: #fee2e2; color: #b91c1c; }
    .chip-default { background: #f3f4f6; color: #374151; }
    .section-title { margin: 0 0 10px; font-size: 18px; }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th, .items-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .items-table th { color: #64748b; font-size: 12px; text-transform: uppercase; }
    .txt-right { text-align: right !important; }
    .muted { color: #6b7280; font-size: 12px; }
    .btn-soft { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 12px; color: #111827; text-decoration: none; font-weight: 600; background: #fff; }
    .btn-soft:hover { background: #f8fafc; }
    .card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
</style>

@php
    $status = (string) ($servoOrder->status ?? 'pending');
    $statusClass = 'chip-' . $status;
    $statusKey = 'storefront.orders.status_'.$status;
    $statusLabel = __($statusKey) !== $statusKey ? __($statusKey) : ucfirst($status);
@endphp

<div class="container order-page">
    @include('frontend.store.partials.flash_status')

    <div class="card">
        <div class="order-header">
            <div>
                <h2 class="order-id">{{ __('storefront.orders.type_partner') }} #{{ $servoOrder->servo_reference ?: $servoOrder->id }}</h2>
                <p class="order-sub">{{ __('lang_v1.placed_on') }} {{ optional($servoOrder->created_at)->format('d M Y, h:i A') }}</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="chip chip-default">{{ __('storefront.orders.type_partner') }}</span>
                <span class="chip {{ in_array($statusClass, ['chip-pending','chip-success','chip-failed']) ? $statusClass : 'chip-default' }}">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    @if (! empty($servoOrder->servo_reference))
        <div class="card">
            <p style="margin:0;"><strong>{{ __('storefront.orders.partner_reference') }}:</strong> {{ $servoOrder->servo_reference }}</p>
        </div>
    @endif

    @if (! empty($servoOrder->error_message) && $servoOrder->status === 'failed')
        <div class="card" style="background:#fef2f2;border:1px solid #fecaca;">
            <p style="margin:0;"><strong>{{ __('storefront.orders.error_message') }}:</strong> {{ $servoOrder->error_message }}</p>
        </div>
    @endif

    @include('frontend.store.account.partials.partner_items', [
        'servoItems' => $servoItems ?? [],
        'servoOrders' => collect([$servoOrder]),
    ])

    <div class="card">
        <div class="card-actions">
            <a href="{{ route('store.account.orders') }}" class="btn-soft">{{ __('lang_v1.back_to_orders') }}</a>
            <a href="{{ route('store.products.index') }}" class="btn-soft">{{ __('lang_v1.continue_shopping') }}</a>
        </div>
    </div>
</div>
@endsection
