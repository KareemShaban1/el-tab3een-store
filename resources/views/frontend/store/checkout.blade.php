@extends('frontend.store.theme_layout')
@push('styles')
<style>
    .card {
        padding: 20px;
        margin: 20px auto;
    }

    .checkout-validation-summary {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 16px 18px;
        margin-bottom: 20px;
        border-radius: 12px;
        border: 1px solid #fecaca;
        background: linear-gradient(135deg, #fef2f2 0%, #fff7f7 100%);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .checkout-validation-summary__icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #fee2e2;
        color: #b91c1c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        line-height: 1;
    }

    .checkout-validation-summary__title {
        margin: 0 0 6px;
        font-size: 1.05rem;
        font-weight: 800;
        color: #991b1b;
    }

    .checkout-validation-summary__hint {
        margin: 0 0 10px;
        font-size: 0.875rem;
        line-height: 1.55;
        color: #7f1d1d;
        opacity: 0.95;
    }

    .checkout-validation-summary__list {
        margin: 0;
        padding-inline-start: 1.15rem;
        font-size: 0.875rem;
        line-height: 1.65;
        color: #450a0a;
    }

    .checkout-client-error {
        display: none;
        margin-bottom: 16px;
    }

    .checkout-client-error.is-visible {
        display: flex;
    }

    .checkout-field-error {
        margin: 6px 0 0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #b91c1c;
        line-height: 1.45;
    }

    .checkout-field-error--block {
        margin-top: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    input.checkout-input-error,
    select.checkout-input-error,
    textarea.checkout-input-error {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.18);
    }

    .checkout-radio-row--error {
        outline: 1px solid rgba(220, 38, 38, 0.35);
        outline-offset: 4px;
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="card" style="max-width:900px;">
    <h2>إتمام الطلب</h2>
    <p class="muted">راجع المنتجات وحدد عنوان الشحن قبل تأكيد الطلب.</p>

    @if ($errors->any())
        <div class="checkout-validation-summary alert error" role="alert" aria-live="assertive">
            <div class="checkout-validation-summary__icon" aria-hidden="true">!</div>
            <div>
                <p class="checkout-validation-summary__title">{{ __('storefront.checkout.errors_title') }}</p>
                <p class="checkout-validation-summary__hint">{{ __('storefront.checkout.errors_hint') }}</p>
                <ul class="checkout-validation-summary__list">
                    @foreach (collect($errors->all())->unique() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="checkout-client-error" class="checkout-validation-summary checkout-client-error alert error" role="alert" aria-live="assertive">
        <div class="checkout-validation-summary__icon" aria-hidden="true">!</div>
        <div>
            <p class="checkout-validation-summary__title">{{ __('storefront.checkout.errors_title') }}</p>
            <p class="checkout-validation-summary__hint" id="checkout-client-error-text"></p>
        </div>
    </div>

    <form method="POST" action="{{ route('store.checkout') }}" id="store-checkout-form" data-empty-cart-msg="{{ e(__('storefront.checkout.empty_cart')) }}">
        @csrf
        <input type="hidden" name="location_id" value="{{ $location_id }}">
        <input type="hidden" name="idempotency_key" value="checkout_{{ uniqid() }}">
        <div id="products-inputs-wrap">
            <input type="hidden" name="products[0][variation_id]" value="{{ old('products.0.variation_id', optional($variation)->id) }}">
            <input type="hidden" name="products[0][quantity]" value="{{ old('products.0.quantity', $qty) }}">
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">المنتجات</h3>
            <!-- @error('products')
                <div class="checkout-field-error checkout-field-error--block" role="status">{{ $message }}</div>
            @enderror
            @error('checkout')
                <div class="checkout-field-error checkout-field-error--block" role="status">{{ $message }}</div>
            @enderror -->
            <div id="checkout-items-wrap" class="muted">جاري تحميل عناصر السلة...</div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">عنوان الشحن</h3>
            @php
                $has_saved_address = !empty(trim((string) $customer->shipping_address));
                $default_option = old('shipping_address_option', $has_saved_address ? 'existing' : 'new');
            @endphp

            @error('shipping_address_option')
                <div class="checkout-field-error checkout-field-error--block" style="margin-bottom:12px;">{{ $message }}</div>
            @enderror

            @if($has_saved_address)
                <label class="{{ $errors->has('shipping_address_option') ? 'checkout-radio-row--error' : '' }}" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">
                    <input type="radio" name="shipping_address_option" value="existing" {{ $default_option === 'existing' ? 'checked' : '' }} style="width:auto;margin-top:4px;">
                    <span>
                        <strong>استخدام العنوان المحفوظ</strong><br>
                        <span class="muted">{{ $customer->name }} - {{ $customer->shipping_address }} {{ $customer->city ? '، '.$customer->city : '' }} {{ $customer->country ? '، '.$customer->country : '' }}</span>
                    </span>
                </label>
            @endif

            <label class="{{ $errors->has('shipping_address_option') ? 'checkout-radio-row--error' : '' }}" style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
                <input type="radio" name="shipping_address_option" value="new" {{ $default_option === 'new' ? 'checked' : '' }} style="width:auto;">
                <span><strong>إدخال عنوان جديد</strong></span>
            </label>

            <div id="new-address-wrap" style="display:none;">
                <div class="row">
                    <div>
                        <label for="checkout-shipping-name">اسم المستلم *</label>
                        <input id="checkout-shipping-name" type="text" name="addresses[shipping_address][shipping_name]" value="{{ old('addresses.shipping_address.shipping_name', $customer->name) }}" class="@error('addresses.shipping_address.shipping_name') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_name') ? 'true' : 'false' }}">
                        @error('addresses.shipping_address.shipping_name')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="checkout-shipping-mobile">رقم الجوال</label>
                        <input id="checkout-shipping-mobile" type="text" name="addresses[shipping_address][shipping_mobile]" value="{{ old('addresses.shipping_address.shipping_mobile', $customer->mobile) }}" class="@error('addresses.shipping_address.shipping_mobile') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_mobile') ? 'true' : 'false' }}">
                        @error('addresses.shipping_address.shipping_mobile')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

  <div class="row">
  <div>
                        <label for="checkout-shipping-state">المحافظة</label>
                        <input id="checkout-shipping-state" type="text" name="addresses[shipping_address][shipping_state]" value="{{ old('addresses.shipping_address.shipping_state', $customer->state) }}" class="@error('addresses.shipping_address.shipping_state') checkout-input-error @enderror" @ariaInvalid('addresses.shipping_address.shipping_state')>
                        @error('addresses.shipping_address.shipping_state')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="checkout-shipping-city">المدينة</label>
                        <input id="checkout-shipping-city" type="text" name="addresses[shipping_address][shipping_city]" value="{{ old('addresses.shipping_address.shipping_city', $customer->city) }}" class="@error('addresses.shipping_address.shipping_city') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_city') ? 'true' : 'false' }}">
                        @error('addresses.shipping_address.shipping_city')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                  
                </div>
                <label for="checkout-shipping-line1">العنوان *</label>
                <input id="checkout-shipping-line1" type="text" name="addresses[shipping_address][shipping_address_line_1]" value="{{ old('addresses.shipping_address.shipping_address_line_1') }}" class="@error('addresses.shipping_address.shipping_address_line_1') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_address_line_1') ? 'true' : 'false' }}">
                @error('addresses.shipping_address.shipping_address_line_1')
                    <p class="checkout-field-error">{{ $message }}</p>
                @enderror

              
                <!-- <div class="row">
                    <div>
                        <label for="checkout-shipping-country">الدولة</label>
                        <input id="checkout-shipping-country" type="text" name="addresses[shipping_address][shipping_country]" value="{{ old('addresses.shipping_address.shipping_country', $customer->country) }}" class="@error('addresses.shipping_address.shipping_country') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_country') ? 'true' : 'false' }}">
                        @error('addresses.shipping_address.shipping_country')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="checkout-shipping-zip">الرمز البريدي</label>
                        <input id="checkout-shipping-zip" type="text" name="addresses[shipping_address][shipping_zip_code]" value="{{ old('addresses.shipping_address.shipping_zip_code', $customer->zip_code) }}" class="@error('addresses.shipping_address.shipping_zip_code') checkout-input-error @enderror" aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_zip_code') ? 'true' : 'false' }}">
                        @error('addresses.shipping_address.shipping_zip_code')
                            <p class="checkout-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div> -->
            </div>
        </div>

        <div class="card">
            <label for="checkout-payment-method">طريقة الدفع</label>
            <select id="checkout-payment-method" name="payment_method" class="@error('payment_method') checkout-input-error @enderror" aria-invalid="{{ $errors->has('payment_method') ? 'true' : 'false' }}">
                <option value="cod" @selected(old('payment_method', 'cod') === 'cod')>الدفع عند الاستلام</option>
                <!-- <option value="online" @selected(old('payment_method') === 'online')>دفع أونلاين</option> -->
            </select>
            @error('payment_method')
                <p class="checkout-field-error">{{ $message }}</p>
            @enderror
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

        form?.addEventListener('input', hideClientError, true);
        form?.addEventListener('change', hideClientError, true);

        form?.addEventListener('submit', function (e) {
            hideClientError();
            if (!cart.length) {
                e.preventDefault();
                const msg = (form && form.dataset && form.dataset.emptyCartMsg) ? form.dataset.emptyCartMsg : '';
                showClientError(msg);
            }
        });
    })();
</script>
@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('.checkout-validation-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
@endif
@endsection

