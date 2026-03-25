@extends('frontend.store.theme_layout')

@php
    $product = $payload['data'];
    $variations = $product['variations'] ?? [];

    $sfStr = static function ($value): string {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return collect($value)->flatten()->filter(static fn ($x) => is_scalar($x))->map(static fn ($x) => (string) $x)->implode(' ');
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '';
    };

    /** Safe translation string for Blade (some lang keys may be arrays). */
    $tx = static function (string $key, array $replace = []) use ($sfStr): string {
        return $sfStr(__($key, $replace));
    };

    $variationLocationMap = [];
    foreach ($variations as $vv) {
        $row = is_array($vv) ? $vv : (method_exists($vv, 'toArray') ? $vv->toArray() : []);
        $vid = $row['variation_id'] ?? null;
        if ($vid !== null && $vid !== '') {
            $variationLocationMap[(string) $vid] = array_values($row['locations'] ?? []);
        }
    }
@endphp

@section('content')
<style>
    .product-page { display: grid; gap: 16px; max-width: 1200px; margin-inline: auto; }
    .product-hero { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
    .product-media { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px; position: sticky; top: 12px; }
    .product-image { width: 100%; max-height: 480px; object-fit: cover; border-radius: 12px; background: #f8fafc; }
    .product-summary { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; display: grid; gap: 14px; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--accent, #ea541a); font-weight: 700; font-size: .9rem; text-decoration: none; margin-bottom: 4px; }
    .back-link:hover { text-decoration: underline; }
    .crumb { color: #6b7280; font-size: 13px; }
    .product-name { margin: 0; font-size: clamp(1.35rem, 3vw, 1.85rem); line-height: 1.25; }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meta-box { background: #f8fafc; border-radius: 10px; padding: 10px; }
    .meta-label { margin: 0; color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; }
    .meta-value { margin: 5px 0 0; font-weight: 700; font-size: 15px; }
    .purchase-block { border-top: 1px solid #f1f5f9; padding-top: 14px; display: grid; gap: 12px; }
    .field-label { display: block; font-weight: 700; font-size: 13px; color: #374151; margin-bottom: 6px; }
    .variant-select {
        width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #e5e7eb;
        font-family: var(--font, inherit); font-size: 15px; font-weight: 600; color: #111827;
        background: #fff; cursor: pointer; appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: left 12px center; background-size: 18px;
        padding-inline-start: 40px; text-align: right;
    }
    .variant-select:focus { outline: none; border-color: var(--accent, #ea541a); box-shadow: 0 0 0 3px rgba(234, 84, 26, .15); }
    .variant-details {
        display: grid; gap: 8px; padding: 14px; background: linear-gradient(180deg, #fafafa 0%, #f8fafc 100%);
        border: 1px solid #e5e7eb; border-radius: 12px;
    }
    .detail-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .detail-key { color: #6b7280; font-size: 13px; }
    .detail-val { font-weight: 700; font-size: 14px; }
    .detail-price { font-size: 22px; font-weight: 800; color: #111827; }
    .loc-block { flex-direction: column; align-items: stretch !important; gap: 8px; }
    .loc-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
    .loc-list li {
        display: flex; justify-content: space-between; align-items: center; gap: 10px;
        padding: 8px 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px;
    }
    .loc-list .loc-qty { font-weight: 800; color: #111827; flex-shrink: 0; }
    .loc-item-inner { flex: 1; min-width: 0; display: grid; gap: 4px; }
    .loc-title-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .loc-title { font-weight: 800; font-size: 14px; color: #111827; }
    .loc-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; background: #dbeafe; color: #1e40af; }
    .loc-meta { font-size: 12px; color: #6b7280; line-height: 1.35; }
    .loc-meta + .loc-meta { margin-top: 2px; }
    .loc-list-header { display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; padding: 0 2px 4px; }
    .chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 700; }
    .chip-ok { background: #dcfce7; color: #166534; }
    .chip-low { background: #fef3c7; color: #92400e; }
    .qty-row { display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: end; }
    .qty-row input[type="number"] {
        width: 100%; max-width: 120px; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb;
        font-family: var(--font, inherit); font-size: 16px; font-weight: 600; text-align: center;
    }
    .qty-row input:focus { outline: none; border-color: var(--accent, #ea541a); }
    .cta-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding-top: 4px; }
    .cta-row .btn { flex: 1; min-width: 140px; justify-content: center; text-align: center; }
    .cta-row .btn.is-disabled { opacity: 0.55; pointer-events: none; cursor: not-allowed; }
    .empty-variations {
        text-align: center; color: #6b7280; padding: 20px; border: 1px dashed #d1d5db; border-radius: 12px; background: #fafafa;
    }
    @media (max-width: 900px) {
        .product-hero { grid-template-columns: 1fr; }
        .product-media { position: static; }
        .meta-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="container product-page">
    <div class="product-hero">
        <div class="product-media">
            <img src="{{ $sfStr($product['image_url'] ?? '') }}" alt="{{ $sfStr($product['name'] ?? '') }}" class="product-image">
        </div>
        <div class="product-summary">
            <div>
                <a href="{{ route('store.products.index') }}" class="back-link">← {{ __('Back to products') }}</a>
            </div>
            <div class="crumb">{{ $sfStr($product['brand'] ?? '') ?: $tx('Brand') }} | {{ $sfStr($product['category'] ?? '') ?: $tx('Category') }}</div>
            <h2 class="product-name">{{ $sfStr($product['name'] ?? '') }}</h2>
            <div class="meta-grid">
                <div class="meta-box">
                    <p class="meta-label">{{ $tx('Unit') }}</p>
                    <p class="meta-value">{{ $sfStr($product['unit'] ?? '') ?: '—' }}</p>
                </div>
                <div class="meta-box">
                    <p class="meta-label">{{ $tx('Options') }}</p>
                    <p class="meta-value">{{ count($variations) }}</p>
                </div>
            </div>

            @if(! empty($variations))
                <div class="purchase-block" id="product-purchase"
                     data-checkout-base="{{ route('store.checkout.form') }}"
                     data-is-customer="{{ auth('customer')->check() ? '1' : '0' }}">
                    <div>
                        <label class="field-label" for="product-variant-select">{{ $tx('Choose variation') }}</label>
                        <select id="product-variant-select" class="variant-select" aria-describedby="variant-details-panel">
                            @foreach($variations as $index => $v)
                                @php
                                    $qty = (float) ($v['qty_available'] ?? 0);
                                    $vName = $sfStr($v['name'] ?? '');
                                    $label = ($vName ?: $tx('Default')) . ' — ' . number_format((float) ($v['price_inc_tax'] ?? 0), 2);
                                    if ($qty <= 5 && $qty > 0) {
                                        $label .= ' (' . $tx('Low stock') . ')';
                                    }
                                @endphp
                                <option value="{{ $sfStr($v['variation_id'] ?? '') }}"
                                    data-sku="{{ e($sfStr($v['sku'] ?? '')) }}"
                                    data-qty="{{ $qty }}"
                                    data-price="{{ (float) ($v['price_inc_tax'] ?? 0) }}"
                                    @selected($index === 0)
                                >{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="variant-details-panel" class="variant-details" role="region" aria-live="polite">
                        <div class="detail-row">
                            <span class="detail-key">{{ $tx('Availability') }}</span>
                            <span id="variant-stock-chip" class="chip chip-ok"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">{{ $tx('SKU') }}</span>
                            <span id="variant-sku" class="detail-val">—</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">{{ $tx('In stock') }}</span>
                            <span id="variant-qty-text" class="detail-val">—</span>
                        </div>
                        <div class="detail-row" style="border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 4px;">
                            <span class="detail-key">{{ $tx('Price') }}</span>
                            <span id="variant-price" class="detail-price">—</span>
                        </div>
                        <div class="detail-row loc-block" style="border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 4px;">
                            <div class="loc-list-header" style="width:100%;">
                                <span>{{ $tx('Location') }}</span>
                                <span>{{ $tx('Quantity') }}</span>
                            </div>
                            <ul id="variant-locations" class="loc-list" aria-label="{{ $tx('Stock by location') }}"></ul>
                        </div>
                    </div>

                    <div class="qty-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="field-label" for="product-variant-qty">{{ $tx('Quantity') }}</label>
                            <input type="number" id="product-variant-qty" name="qty" value="1" min="1" step="1">
                        </div>
                    </div>

                    <div class="cta-row">
                        @auth('customer')
                            <a id="product-buy-btn" class="btn" href="#">{{ $tx('Buy now') }}</a>
                        @else
                            <a id="product-buy-btn" class="btn secondary" href="{{ route('store.auth.login.form') }}">{{ $tx('Login to purchase') }}</a>
                        @endauth
                    </div>
                </div>
            @else
                <div class="empty-variations">{{ $tx('No in-stock variations are available for this product right now.') }}</div>
            @endif
        </div>
    </div>
</div>

@if(! empty($variations))
<script type="application/json" id="store-product-locations-json">@json($variationLocationMap)</script>
<script>
(function () {
    var root = document.getElementById('product-purchase');
    if (!root) return;

    var select = document.getElementById('product-variant-select');
    var qtyInput = document.getElementById('product-variant-qty');
    var buyBtn = document.getElementById('product-buy-btn');
    var skuEl = document.getElementById('variant-sku');
    var qtyTextEl = document.getElementById('variant-qty-text');
    var priceEl = document.getElementById('variant-price');
    var chipEl = document.getElementById('variant-stock-chip');
    var locListEl = document.getElementById('variant-locations');
    var emptyLocText = @json($tx('No location stock to show'));
    var checkoutBranchLabel = @json($tx('Checkout branch'));

    var locMap = {};
    var locJsonEl = document.getElementById('store-product-locations-json');
    if (locJsonEl && locJsonEl.textContent) {
        try {
            locMap = JSON.parse(locJsonEl.textContent.trim()) || {};
        } catch (err) {
            locMap = {};
        }
    }

    var checkoutBase = root.getAttribute('data-checkout-base') || '';
    var isCustomer = root.getAttribute('data-is-customer') === '1';

    function getSelectedOption() {
        return select.options[select.selectedIndex];
    }

    function formatNum(n) {
        return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function syncDetails() {
        var opt = getSelectedOption();
        if (!opt) return;

        var sku = opt.getAttribute('data-sku') || '';
        var qtyAvail = parseFloat(opt.getAttribute('data-qty') || '0') || 0;
        var price = parseFloat(opt.getAttribute('data-price') || '0') || 0;
        var vid = select.value;
        var locs = locMap[vid];
        if (!Array.isArray(locs)) {
            locs = locMap[String(vid)] || [];
        }

        skuEl.textContent = sku || '—';
        qtyTextEl.textContent = formatNum(qtyAvail);
        priceEl.textContent = formatNum(price);

        if (locListEl) {
            locListEl.innerHTML = '';
            try {
                if (Array.isArray(locs) && locs.length) {
                    locs.forEach(function (row) {
                        var li = document.createElement('li');

                        var inner = document.createElement('div');
                        inner.className = 'loc-item-inner';

                        var titleRow = document.createElement('div');
                        titleRow.className = 'loc-title-row';
                        var title = document.createElement('span');
                        title.className = 'loc-title';
                        title.textContent = row.name || ('#' + row.location_id);
                        titleRow.appendChild(title);
                        if (row.is_checkout_location) {
                            var badge = document.createElement('span');
                            badge.className = 'loc-badge';
                            badge.textContent = checkoutBranchLabel;
                            titleRow.appendChild(badge);
                        }
                        inner.appendChild(titleRow);

                        if (row.address) {
                            var addr = document.createElement('div');
                            addr.className = 'loc-meta';
                            addr.textContent = row.address;
                            inner.appendChild(addr);
                        }
                        if (row.mobile) {
                            var phone = document.createElement('div');
                            phone.className = 'loc-meta';
                            phone.textContent = row.mobile;
                            inner.appendChild(phone);
                        }

                        var q = document.createElement('span');
                        q.className = 'loc-qty';
                        q.textContent = formatNum(parseFloat(row.qty_available) || 0);

                        li.appendChild(inner);
                        li.appendChild(q);
                        locListEl.appendChild(li);
                    });
                } else {
                    var emptyLi = document.createElement('li');
                    emptyLi.style.justifyContent = 'center';
                    emptyLi.style.color = '#6b7280';
                    emptyLi.textContent = emptyLocText;
                    locListEl.appendChild(emptyLi);
                }
            } catch (e) {
                var errLi = document.createElement('li');
                errLi.style.justifyContent = 'center';
                errLi.style.color = '#6b7280';
                errLi.textContent = emptyLocText;
                locListEl.appendChild(errLi);
            }
        }

        if (qtyAvail <= 5 && qtyAvail > 0) {
            chipEl.className = 'chip chip-low';
            chipEl.textContent = @json($tx('Low stock'));
        } else if (qtyAvail > 0) {
            chipEl.className = 'chip chip-ok';
            chipEl.textContent = @json($tx('In stock'));
        } else {
            chipEl.className = 'chip chip-low';
            chipEl.textContent = @json($tx('Out of stock'));
        }

        var maxQ = Math.max(1, Math.floor(qtyAvail));
        if (qtyAvail > 0 && qtyAvail < 1) {
            maxQ = 1;
        }
        qtyInput.max = maxQ;
        var q = parseInt(qtyInput.value, 10) || 1;
        if (q < 1) q = 1;
        if (q > maxQ) qtyInput.value = maxQ;
        else qtyInput.value = q;

        if (isCustomer && buyBtn && checkoutBase) {
            if (qtyAvail <= 0) {
                buyBtn.classList.add('is-disabled');
                buyBtn.setAttribute('aria-disabled', 'true');
                buyBtn.setAttribute('href', '#');
            } else {
                buyBtn.classList.remove('is-disabled');
                buyBtn.removeAttribute('aria-disabled');
                var qv = parseInt(qtyInput.value, 10) || 1;
                var u = new URL(checkoutBase, window.location.origin);
                u.searchParams.set('variation_id', vid);
                u.searchParams.set('qty', String(qv));
                buyBtn.setAttribute('href', u.toString());
            }
        }
    }

    select.addEventListener('change', syncDetails);
    qtyInput.addEventListener('input', function () {
        var opt = getSelectedOption();
        var max = opt ? parseFloat(opt.getAttribute('data-qty') || '0') : 0;
        var maxQ = Math.max(1, Math.floor(max));
        if (max > 0 && max < 1) maxQ = 1;
        var q = parseInt(qtyInput.value, 10) || 1;
        if (q < 1) qtyInput.value = 1;
        else if (q > maxQ) qtyInput.value = maxQ;
        syncDetails();
    });

    syncDetails();
})();
</script>
@endif
@endsection
