@extends('frontend.store.theme_layout')

@section('content')
<div class="card" style="max-width:900px;">
    <h2>إتمام الطلب</h2>
    <p class="muted">راجع المنتجات وحدد عنوان الشحن قبل تأكيد الطلب.</p>

    <form method="POST" action="{{ route('store.checkout') }}" id="store-checkout-form">
        @csrf
        <input type="hidden" name="location_id" value="{{ $location_id }}">
        <input type="hidden" name="idempotency_key" value="checkout_{{ uniqid() }}">
        <div id="products-inputs-wrap">
            <input type="hidden" name="products[0][variation_id]" value="{{ old('products.0.variation_id', optional($variation)->id) }}">
            <input type="hidden" name="products[0][quantity]" value="{{ old('products.0.quantity', $qty) }}">
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">المنتجات</h3>
            <div id="checkout-items-wrap" class="muted">جاري تحميل عناصر السلة...</div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">عنوان الشحن</h3>
            @php
                $has_saved_address = !empty(trim((string) $customer->shipping_address));
                $default_option = old('shipping_address_option', $has_saved_address ? 'existing' : 'new');
            @endphp

            @if($has_saved_address)
                <label style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">
                    <input type="radio" name="shipping_address_option" value="existing" {{ $default_option === 'existing' ? 'checked' : '' }} style="width:auto;margin-top:4px;">
                    <span>
                        <strong>استخدام العنوان المحفوظ</strong><br>
                        <span class="muted">{{ $customer->name }} - {{ $customer->shipping_address }} {{ $customer->city ? '، '.$customer->city : '' }} {{ $customer->country ? '، '.$customer->country : '' }}</span>
                    </span>
                </label>
            @endif

            <label style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
                <input type="radio" name="shipping_address_option" value="new" {{ $default_option === 'new' ? 'checked' : '' }} style="width:auto;">
                <span><strong>إدخال عنوان جديد</strong></span>
            </label>

            <div id="new-address-wrap" style="display:none;">
                <div class="row">
                    <div>
                        <label>اسم المستلم *</label>
                        <input type="text" name="addresses[shipping_address][shipping_name]" value="{{ old('addresses.shipping_address.shipping_name', $customer->name) }}">
                    </div>
                    <div>
                        <label>رقم الجوال</label>
                        <input type="text" name="addresses[shipping_address][shipping_mobile]" value="{{ old('addresses.shipping_address.shipping_mobile', $customer->mobile) }}">
                    </div>
                </div>
                <label>العنوان *</label>
                <input type="text" name="addresses[shipping_address][shipping_address_line_1]" value="{{ old('addresses.shipping_address.shipping_address_line_1') }}">

                <div class="row">
                    <div>
                        <label>المدينة</label>
                        <input type="text" name="addresses[shipping_address][shipping_city]" value="{{ old('addresses.shipping_address.shipping_city', $customer->city) }}">
                    </div>
                    <div>
                        <label>المحافظة</label>
                        <input type="text" name="addresses[shipping_address][shipping_state]" value="{{ old('addresses.shipping_address.shipping_state', $customer->state) }}">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>الدولة</label>
                        <input type="text" name="addresses[shipping_address][shipping_country]" value="{{ old('addresses.shipping_address.shipping_country', $customer->country) }}">
                    </div>
                    <div>
                        <label>الرمز البريدي</label>
                        <input type="text" name="addresses[shipping_address][shipping_zip_code]" value="{{ old('addresses.shipping_address.shipping_zip_code', $customer->zip_code) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <label>طريقة الدفع</label>
            <select name="payment_method">
                <option value="cod" @selected(old('payment_method', 'cod') === 'cod')>الدفع عند الاستلام</option>
                <option value="online" @selected(old('payment_method') === 'online')>دفع أونلاين</option>
            </select>
        </div>

        <button class="btn" type="submit">تأكيد الطلب</button>
    </form>
</div>

<script>
    (function () {
        const CART_STORAGE_KEY = 'store_cart_v1';
        const itemsWrap = document.getElementById('checkout-items-wrap');
        const inputsWrap = document.getElementById('products-inputs-wrap');
        const newAddressWrap = document.getElementById('new-address-wrap');
        const optionInputs = document.querySelectorAll('input[name="shipping_address_option"]');
        const form = document.getElementById('store-checkout-form');

        function toggleAddressForm() {
            const selected = document.querySelector('input[name="shipping_address_option"]:checked')?.value;
            if (newAddressWrap) {
                newAddressWrap.style.display = selected === 'new' ? 'block' : 'none';
            }
        }

        function readCart() {
            try {
                return JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
            } catch (e) {
                return [];
            }
        }

        function renderItems(cart) {
            if (!itemsWrap) return;
            if (!cart.length) {
                itemsWrap.innerHTML = 'السلة فارغة. ارجع إلى المتجر وأضف منتجات أولاً.';
                return;
            }
            itemsWrap.innerHTML = cart.map((item) => `
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0;">
                    <span>${item.name || 'منتج'}</span>
                    <span>الكمية: ${Number(item.qty || 1)}</span>
                </div>
            `).join('');
        }

        function renderProductInputs(cart) {
            if (!inputsWrap) return;
            if (!cart.length) return;
            inputsWrap.innerHTML = cart.map((item, idx) => `
                <input type="hidden" name="products[${idx}][variation_id]" value="${Number(item.variation_id || 0)}">
                <input type="hidden" name="products[${idx}][quantity]" value="${Math.max(1, Number(item.qty || 1))}">
            `).join('');
        }

        const cart = readCart().filter((item) => Number(item.variation_id) > 0 && Number(item.qty) > 0);
        renderItems(cart);
        renderProductInputs(cart);
        toggleAddressForm();
        optionInputs.forEach((el) => el.addEventListener('change', toggleAddressForm));

        form?.addEventListener('submit', function (e) {
            if (!cart.length) {
                e.preventDefault();
                alert('السلة فارغة');
            }
        });
    })();
</script>
@endsection

