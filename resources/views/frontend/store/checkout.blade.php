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

.checkout-location-select {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #ddd;
	border-radius: 8px;
	background: #fff;
	font-size: 0.95rem;
}

.checkout-location-select:disabled {
	background: #f5f5f5;
	color: #888;
}

#checkout-area-row {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
	margin-top: 12px;
}

@media (max-width: 640px) {
	#checkout-area-row {
		grid-template-columns: 1fr;
	}
}

.checkout-items-table {
	width: 100%;
	border-collapse: collapse;
}

.checkout-items-table th,
.checkout-items-table td {
	padding: 10px 8px;
	border-bottom: 1px solid #eee;
	text-align: right;
	vertical-align: middle;
}

.checkout-items-table th {
	font-size: 0.8125rem;
	color: #666;
	font-weight: 700;
}

.checkout-item-name {
	font-weight: 600;
	color: #111;
}

.checkout-item-meta {
	font-size: 0.8125rem;
	color: #888;
	margin-top: 4px;
}

.checkout-order-summary {
	margin-top: 16px;
	padding-top: 14px;
	border-top: 2px solid #eee;
}

.checkout-summary-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	padding: 8px 0;
	font-size: 0.9375rem;
}

.checkout-summary-row--total {
	margin-top: 8px;
	padding-top: 14px;
	border-top: 2px solid #111;
	font-size: 1.125rem;
	font-weight: 800;
}

.checkout-summary-label {
	color: #555;
}

.checkout-summary-value {
	font-weight: 700;
	color: #111;
	white-space: nowrap;
}

.checkout-summary-value--accent {
	color: #047857;
}

.checkout-summary-value--muted {
	color: #999;
	font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="card" style="max-width:900px;">
	<h2>إتمام الطلب</h2>
	<p class="muted">راجع المنتجات وحدد عنوان الشحن قبل تأكيد الطلب.</p>

	@include('frontend.store.partials.flash_status')

	@if ($errors->any())
	<div class="checkout-validation-summary alert error" role="alert" aria-live="assertive">
		<div class="checkout-validation-summary__icon" aria-hidden="true">!</div>
		<div>
			<p class="checkout-validation-summary__title">{{ __('storefront.checkout.errors_title') }}
			</p>
			<p class="checkout-validation-summary__hint">{{ __('storefront.checkout.errors_hint') }}
			</p>
			<ul class="checkout-validation-summary__list">
				@foreach (collect($errors->all())->unique() as $error)
				<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	</div>
	@endif

	<div id="checkout-client-error" class="checkout-validation-summary checkout-client-error alert error"
		role="alert" aria-live="assertive">
		<div class="checkout-validation-summary__icon" aria-hidden="true">!</div>
		<div>
			<p class="checkout-validation-summary__title">{{ __('storefront.checkout.errors_title') }}
			</p>
			<p class="checkout-validation-summary__hint" id="checkout-client-error-text"></p>
		</div>
	</div>

	<form method="POST" action="{{ route('store.checkout') }}" id="store-checkout-form"
		data-empty-cart-msg="{{ e(__('storefront.checkout.empty_cart')) }}">
		@csrf
		<input type="hidden" name="location_id" value="{{ $location_id }}">
		<input type="hidden" name="idempotency_key" value="checkout_{{ uniqid() }}">
		<div id="products-inputs-wrap">
			<input type="hidden" name="products[0][variation_id]"
				value="{{ old('products.0.variation_id', optional($variation)->id) }}">
			<input type="hidden" name="products[0][quantity]"
				value="{{ old('products.0.quantity', $qty) }}">
		</div>

		<div class="card">
			<h3 style="margin-bottom:10px;">المنتجات</h3>
			<div id="checkout-items-wrap" class="muted">جاري تحميل عناصر السلة...</div>

			<div class="checkout-order-summary" id="checkout-order-summary">
				<div class="checkout-summary-row">
					<span class="checkout-summary-label">مجموع المنتجات</span>
					<span class="checkout-summary-value" id="checkout-subtotal-value">0
						ج.م</span>
				</div>
				@if(!empty($locations_fees_enabled))
				<div class="checkout-summary-row" id="checkout-delivery-fee-row">
					<span
						class="checkout-summary-label">{{ __('locations_fees.delivery_fee') }}</span>
					<span class="checkout-summary-value checkout-summary-value--muted"
						id="checkout-delivery-fee-value">—</span>
				</div>
				@endif
				<div class="checkout-summary-row checkout-summary-row--total">
					<span>الإجمالي</span>
					<span id="checkout-grand-total-value">0 ج.م</span>
				</div>
			</div>
		</div>

		<div class="card">
			<h3 style="margin-bottom:10px;">عنوان الشحن</h3>
			@php
			$has_saved_address = !empty(trim((string) $customer->shipping_address));
			$default_option = old('shipping_address_option', $has_saved_address ? 'existing' : 'new');
			@endphp

			@error('shipping_address_option')
			<div class="checkout-field-error checkout-field-error--block" style="margin-bottom:12px;">
				{{ $message }}</div>
			@enderror

			@if($has_saved_address)
			<label class="{{ $errors->has('shipping_address_option') ? 'checkout-radio-row--error' : '' }}"
				style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">
				<input type="radio" name="shipping_address_option" value="existing"
					{{ $default_option === 'existing' ? 'checked' : '' }}
					style="width:auto;margin-top:4px;">
				<span>
					<strong>استخدام العنوان المحفوظ</strong><br>
					<span class="muted">{{ $customer->name }} -
						{{ $customer->shipping_address }}
						{{ $customer->city ? '، '.$customer->city : '' }}
						{{ $customer->country ? '، '.$customer->country : '' }}</span>
				</span>
			</label>
			@endif

			<label class="{{ $errors->has('shipping_address_option') ? 'checkout-radio-row--error' : '' }}"
				style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
				<input type="radio" name="shipping_address_option" value="new"
					{{ $default_option === 'new' ? 'checked' : '' }} style="width:auto;">
				<span><strong>إدخال عنوان جديد</strong></span>
			</label>

			<div id="new-address-wrap" style="display:none;">
				<div class="row">
					<div>
						<label for="checkout-shipping-name">اسم المستلم *</label>
						<input id="checkout-shipping-name" type="text"
							name="addresses[shipping_address][shipping_name]"
							value="{{ old('addresses.shipping_address.shipping_name', $customer->name) }}"
							class="@error('addresses.shipping_address.shipping_name') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_name') ? 'true' : 'false' }}">
						@error('addresses.shipping_address.shipping_name')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					<div>
						<label for="checkout-shipping-mobile">رقم الجوال</label>
						<input id="checkout-shipping-mobile" type="text"
							name="addresses[shipping_address][shipping_mobile]"
							value="{{ old('addresses.shipping_address.shipping_mobile', $customer->mobile) }}"
							class="@error('addresses.shipping_address.shipping_mobile') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_mobile') ? 'true' : 'false' }}">
						@error('addresses.shipping_address.shipping_mobile')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
				</div>

				<div class="row">
					@if(!empty($locations_fees_enabled))
					<input type="hidden"
						name="addresses[shipping_address][shipping_state]"
						id="checkout-shipping-state-hidden"
						value="{{ old('addresses.shipping_address.shipping_state') }}">
					<input type="hidden" name="addresses[shipping_address][shipping_city]"
						id="checkout-shipping-city-hidden"
						value="{{ old('addresses.shipping_address.shipping_city') }}">
					<input type="hidden" name="addresses[shipping_address][shipping_area]"
						id="checkout-shipping-area-hidden"
						value="{{ old('addresses.shipping_address.shipping_area') }}">
					<input type="hidden"
						name="addresses[shipping_address][lf_governorate_id]"
						id="checkout-lf-governorate-id"
						value="{{ old('addresses.shipping_address.lf_governorate_id') }}">
					<input type="hidden" name="addresses[shipping_address][lf_city_id]"
						id="checkout-lf-city-id"
						value="{{ old('addresses.shipping_address.lf_city_id') }}">
					<input type="hidden" name="addresses[shipping_address][lf_area_id]"
						id="checkout-lf-area-id"
						value="{{ old('addresses.shipping_address.lf_area_id') }}">

					<div>
						<label for="checkout-lf-governorate">المحافظة *</label>
						<select id="checkout-lf-governorate"
							class="checkout-location-select @error('addresses.shipping_address.lf_governorate_id') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.lf_governorate_id') ? 'true' : 'false' }}">
							<option value="">
								{{ __('locations_fees.select_governorate') }}
							</option>
							@foreach ($lf_governorates as $governorate)
							<option value="{{ $governorate->id }}"
								@selected((string)
								old('addresses.shipping_address.lf_governorate_id')===(string)
								$governorate->
								id)>{{ $governorate->name }}</option>
							@endforeach
						</select>
						@error('addresses.shipping_address.lf_governorate_id')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					<div>
						<label for="checkout-lf-city">المدينة *</label>
						<select id="checkout-lf-city" disabled
							class="checkout-location-select @error('addresses.shipping_address.lf_city_id') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.lf_city_id') ? 'true' : 'false' }}">
							<option value="">
								{{ __('locations_fees.select_city') }}
							</option>
						</select>
						@error('addresses.shipping_address.lf_city_id')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					@else
					<div>
						<label for="checkout-shipping-state">المحافظة</label>
						<input id="checkout-shipping-state" type="text"
							name="addresses[shipping_address][shipping_state]"
							value="{{ old('addresses.shipping_address.shipping_state', $customer->state) }}"
							class="@error('addresses.shipping_address.shipping_state') checkout-input-error @enderror"
							@ariaInvalid('addresses.shipping_address.shipping_state')>
						@error('addresses.shipping_address.shipping_state')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					<div>
						<label for="checkout-shipping-city">المدينة</label>
						<input id="checkout-shipping-city" type="text"
							name="addresses[shipping_address][shipping_city]"
							value="{{ old('addresses.shipping_address.shipping_city', $customer->city) }}"
							class="@error('addresses.shipping_address.shipping_city') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_city') ? 'true' : 'false' }}">
						@error('addresses.shipping_address.shipping_city')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					@endif

				</div>

				@if(!empty($locations_fees_enabled))
				<div id="checkout-area-row" style="display:none;">
					<div>
						<label for="checkout-lf-area">المنطقة *</label>
						<select id="checkout-lf-area" disabled
							class="checkout-location-select @error('addresses.shipping_address.lf_area_id') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.lf_area_id') ? 'true' : 'false' }}">
							<option value="">
								{{ __('locations_fees.select_area') }}
							</option>
						</select>
						@error('addresses.shipping_address.lf_area_id')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
					<div id="checkout-custom-area-wrap" style="display:none;">
						<label for="checkout-lf-custom-area">{{ __('locations_fees.custom_area') }}
							*</label>
						<input id="checkout-lf-custom-area" type="text"
							name="addresses[shipping_address][lf_custom_area]"
							value="{{ old('addresses.shipping_address.lf_custom_area') }}"
							placeholder="{{ __('locations_fees.custom_area_placeholder') }}"
							class="@error('addresses.shipping_address.lf_custom_area') checkout-input-error @enderror"
							aria-invalid="{{ $errors->has('addresses.shipping_address.lf_custom_area') ? 'true' : 'false' }}">
						@error('addresses.shipping_address.lf_custom_area')
						<p class="checkout-field-error">{{ $message }}</p>
						@enderror
					</div>
				</div>

				@endif
				<label for="checkout-shipping-line1">العنوان *</label>
				<input id="checkout-shipping-line1" type="text"
					name="addresses[shipping_address][shipping_address_line_1]"
					value="{{ old('addresses.shipping_address.shipping_address_line_1') }}"
					class="@error('addresses.shipping_address.shipping_address_line_1') checkout-input-error @enderror"
					aria-invalid="{{ $errors->has('addresses.shipping_address.shipping_address_line_1') ? 'true' : 'false' }}">
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
			<select id="checkout-payment-method" name="payment_method"
				class="checkout-location-select @error('payment_method') checkout-input-error @enderror"
				aria-invalid="{{ $errors->has('payment_method') ? 'true' : 'false' }}">
				<option value="cod" @selected(old('payment_method', 'cod' )==='cod' )>الدفع عند
					الاستلام</option>
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
function showClientError(msg) {
	const wrap = document.getElementById('checkout-client-error');
	const text = document.getElementById('checkout-client-error-text');
	if (!wrap || !text) return;
	text.textContent = msg || '';
	wrap.classList.add('is-visible');
}

function hideClientError() {
	const wrap = document.getElementById('checkout-client-error');
	const text = document.getElementById('checkout-client-error-text');
	if (!wrap || !text) return;
	text.textContent = '';
	wrap.classList.remove('is-visible');
}

function checkoutFormatMoney(amount) {
	const value = Number(amount || 0);
	if (typeof fmt === 'function') {
		return fmt(value);
	}
	return value.toLocaleString('ar-EG') + ' ج.م';
}

function calcCheckoutSubtotal(cart) {
	return (cart || []).reduce((sum, item) => {
		return sum + (Number(item.price || 0) * Math.max(1, Number(item.qty || 1)));
	}, 0);
}

window.updateCheckoutOrderSummary = function(deliveryFee) {
	const cart = (typeof window.__checkoutReadCart === 'function') ? window.__checkoutReadCart() : [];
	const subtotal = calcCheckoutSubtotal(cart);
	const fee = Number(deliveryFee);
	const hasFee = Number.isFinite(fee) && fee >= 0;
	const delivery = hasFee ? fee : null;
	const grandTotal = subtotal + (delivery ?? 0);

	const subtotalEl = document.getElementById('checkout-subtotal-value');
	const deliveryEl = document.getElementById('checkout-delivery-fee-value');
	const grandEl = document.getElementById('checkout-grand-total-value');

	if (subtotalEl) subtotalEl.textContent = checkoutFormatMoney(subtotal);
	if (deliveryEl) {
		if (delivery === null) {
			deliveryEl.textContent = '—';
			deliveryEl.classList.add('checkout-summary-value--muted');
			deliveryEl.classList.remove('checkout-summary-value--accent');
		} else {
			deliveryEl.textContent = checkoutFormatMoney(delivery);
			deliveryEl.classList.remove('checkout-summary-value--muted');
			deliveryEl.classList.add('checkout-summary-value--accent');
		}
	}
	if (grandEl) grandEl.textContent = checkoutFormatMoney(grandTotal);
};

(function() {
	const CART_STORAGE_KEY = 'store_cart_v1';
	const itemsWrap = document.getElementById('checkout-items-wrap');
	const inputsWrap = document.getElementById('products-inputs-wrap');
	const newAddressWrap = document.getElementById('new-address-wrap');
	const optionInputs = document.querySelectorAll('input[name="shipping_address_option"]');
	const form = document.getElementById('store-checkout-form');

	function toggleAddressForm() {
		const selected = document.querySelector('input[name="shipping_address_option"]:checked')
			?.value;
		if (newAddressWrap) {
			newAddressWrap.style.display = selected === 'new' ? 'block' : 'none';
		}
		@if(!empty($locations_fees_enabled))
		if (selected === 'new' && typeof window.__lfCheckoutInit === 'function') {
			window.__lfCheckoutInit();
		}
		@endif
	}

	function readCart() {
		try {
			const parsed = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
			if (!Array.isArray(parsed)) {
				return [];
			}

			return parsed
				.filter((item) => Number(item?.variation_id) > 0 && Number(item
					?.qty) > 0)
				.map((item) => ({
					id: Number(item.id || 0),
					variation_id: Number(item
						.variation_id || 0),
					name: String(item.name || ''),
					price: Number(item.price || 0),
					qty: Math.max(1, Number(item.qty || 1)),
					img: String(item.img || ''),
					source: item.source ? String(item
						.source) : null,
				}));
		} catch (e) {
			return [];
		}
	}

	window.__checkoutReadCart = readCart;

	function renderItems(cart) {
		if (!itemsWrap) return;
		if (!cart.length) {
			itemsWrap.innerHTML = 'السلة فارغة. ارجع إلى المتجر وأضف منتجات أولاً.';
			updateCheckoutOrderSummary(null);
			return;
		}

		const rows = cart.map((item) => {
			const qty = Math.max(1, Number(item.qty || 1));
			const unitPrice = Number(item.price || 0);
			const lineTotal = unitPrice * qty;
			const sourceLabel = item.source === 'servo' ? 'منتج شريك' :
				'منتج المتجر';

			return `
                    <tr>
                        <td>
                            <div class="checkout-item-name">${item.name || 'منتج'}</div>
                            <div class="checkout-item-meta">${sourceLabel}</div>
                        </td>
                        <td>${qty}</td>
                        <td>${checkoutFormatMoney(unitPrice)}</td>
                        <td><strong>${checkoutFormatMoney(lineTotal)}</strong></td>
                    </tr>
                `;
		}).join('');

		itemsWrap.innerHTML = `
                <table class="checkout-items-table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;

		updateCheckoutOrderSummary(window.__checkoutDeliveryFee ?? null);
	}

	function renderProductInputs(cart) {
		if (!inputsWrap) return;
		if (!cart.length) return;
		inputsWrap.innerHTML = cart.map((item, idx) => `
                <input type="hidden" name="products[${idx}][variation_id]" value="${Number(item.variation_id || 0)}">
                <input type="hidden" name="products[${idx}][quantity]" value="${Math.max(1, Number(item.qty || 1))}">
                <input type="hidden" name="products[${idx}][product_id]" value="${Number(item.id || 0)}">
                <input type="hidden" name="products[${idx}][name]" value="${String(item.name || '').replace(/"/g, '&quot;')}">
                <input type="hidden" name="products[${idx}][price]" value="${Number(item.price || 0)}">
                ${item.source === 'servo' ? `<input type="hidden" name="products[${idx}][source]" value="servo">` : ''}
            `).join('');
	}

	const cart = readCart().filter((item) => Number(item.variation_id) > 0 && Number(item.qty) > 0);
	renderItems(cart);
	renderProductInputs(cart);
	toggleAddressForm();
	optionInputs.forEach((el) => el.addEventListener('change', toggleAddressForm));

	form?.addEventListener('input', hideClientError, true);
	form?.addEventListener('change', hideClientError, true);

	form?.addEventListener('submit', async function(e) {
		hideClientError();
		const latestCart = readCart();
		renderProductInputs(latestCart);
		if (!latestCart.length) {
			e.preventDefault();
			const msg = (form && form.dataset && form.dataset
					.emptyCartMsg) ? form.dataset
				.emptyCartMsg : '';
			showClientError(msg);
			return;
		}

		e.preventDefault();
		const submitBtn = form.querySelector('[type="submit"]');
		if (submitBtn) {
			submitBtn.disabled = true;
		}

		try {
			const response = await fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json',
				},
			});

			let data = {};
			try {
				data = await response.json();
			} catch (parseError) {
				data = {};
			}

			if (response.status === 422) {
				const errors = data.errors || {};
				const messages = Object.values(errors).flat().filter(Boolean);
				showClientError(messages.join(' ') || data.message || 'تعذر إتمام الطلب.');
				return;
			}

			if (response.ok && data.success) {
				if (data.clear_cart) {
					if (typeof window.clearStoreCart === 'function') {
						window.clearStoreCart();
					} else {
						try {
							localStorage.removeItem(CART_STORAGE_KEY);
						} catch (storageError) {}
					}
				}

				window.location.href = data.redirect_url || @json(route('welcome'));
				return;
			}

			const errorMessages = Array.isArray(data.error_messages) ? data.error_messages : [];
			showClientError(data.msg || errorMessages.join(' ') || 'تعذر إتمام الطلب.');
		} catch (error) {
			showClientError('تعذر إتمام الطلب. حاول مرة أخرى.');
		} finally {
			if (submitBtn) {
				submitBtn.disabled = false;
			}
		}
	});

	@if(!empty($locations_fees_enabled))
	window.__lfCheckoutInit = initLocationsFeesCheckout;
	if (document.querySelector('input[name="shipping_address_option"]:checked')?.value === 'new') {
		initLocationsFeesCheckout();
	}
	@endif
})();

@if(!empty($locations_fees_enabled))

function initLocationsFeesCheckout() {
	if (window.__lfCheckoutReady) {
		return;
	}
	window.__lfCheckoutReady = true;

	const AREA_OTHER = '__other__';
	const governorateSelect = document.getElementById('checkout-lf-governorate');
	const citySelect = document.getElementById('checkout-lf-city');
	const areaSelect = document.getElementById('checkout-lf-area');
	const areaRow = document.getElementById('checkout-area-row');
	const customAreaWrap = document.getElementById('checkout-custom-area-wrap');
	const customAreaInput = document.getElementById('checkout-lf-custom-area');
	const hiddenGovernorateId = document.getElementById('checkout-lf-governorate-id');
	const hiddenCityId = document.getElementById('checkout-lf-city-id');
	const hiddenAreaId = document.getElementById('checkout-lf-area-id');
	const hiddenState = document.getElementById('checkout-shipping-state-hidden');
	const hiddenCity = document.getElementById('checkout-shipping-city-hidden');
	const hiddenArea = document.getElementById('checkout-shipping-area-hidden');

	const routes = {
		governorates: @json(route('store.locations.governorates')),
		cities: @json(route('store.locations.cities')),
		areas: @json(route('store.locations.areas')),
		fee: @json(route('store.locations.fee')),
	};

	const oldGovernorateId = hiddenGovernorateId?.value || '';
	const oldCityId = hiddenCityId?.value || '';
	const oldAreaId = hiddenAreaId?.value || '';
	const oldCustomArea = customAreaInput?.value || '';

	function setSelectOptions(select, items, placeholder, selectedValue) {
		if (!select) return;
		select.innerHTML = `<option value="">${placeholder}</option>`;
		items.forEach((item) => {
			const opt = document.createElement('option');
			opt.value = String(item.id);
			opt.textContent = item.name;
			if (String(selectedValue) === String(item.id)) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});
	}

	function resetFee() {
		window.__checkoutDeliveryFee = null;
		updateCheckoutOrderSummary(null);
	}

	function setDeliveryFee(amount) {
		window.__checkoutDeliveryFee = Number(amount || 0);
		updateCheckoutOrderSummary(window.__checkoutDeliveryFee);
	}

	async function fetchJson(url) {
		const response = await fetch(url, {
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			credentials: 'same-origin',
		});
		if (!response.ok) {
			throw new Error('Request failed');
		}
		return response.json();
	}

	async function updateFee() {
		const governorateId = governorateSelect?.value;
		const cityId = citySelect?.value;
		const areaId = areaSelect?.value;
		const customArea = customAreaInput?.value?.trim() || '';

		if (!governorateId || !cityId) {
			resetFee();
			return;
		}

		if (!areaId) {
			resetFee();
			return;
		}

		const params = new URLSearchParams({
			governorate_id: governorateId,
			city_id: cityId,
		});

		if (areaId === AREA_OTHER) {
			if (!customArea) {
				resetFee();
				return;
			}
			params.set('custom_area', customArea);
		} else {
			params.set('area_id', areaId);
		}

		try {
			const result = await fetchJson(`${routes.fee}?${params.toString()}`);
			if (!result.success) {
				resetFee();
				return;
			}

			if (hiddenState) hiddenState.value = result.governorate_name || '';
			if (hiddenCity) hiddenCity.value = result.city_name || '';
			if (hiddenArea) hiddenArea.value = result.area_name || customArea || '';
			if (hiddenGovernorateId) hiddenGovernorateId.value = governorateId;
			if (hiddenCityId) hiddenCityId.value = cityId;
			if (hiddenAreaId) hiddenAreaId.value = areaId === AREA_OTHER ? '' : areaId;

			setDeliveryFee(result.fee || 0);
		} catch (err) {
			resetFee();
		}
	}

	async function loadGovernorates() {
		if (governorateSelect && governorateSelect.options.length > 1) {
			if (oldGovernorateId) {
				governorateSelect.value = oldGovernorateId;
				await loadCities(oldGovernorateId, oldCityId);
			}
			return;
		}

		const result = await fetchJson(routes.governorates);
		setSelectOptions(governorateSelect, result.data || [], @json(__(
				'locations_fees.select_governorate')),
			oldGovernorateId);
		if (oldGovernorateId) {
			await loadCities(oldGovernorateId, oldCityId);
		}
	}

	async function loadCities(governorateId, selectedCityId) {
		if (!governorateId) {
			citySelect.innerHTML =
				`<option value="">${@json(__('locations_fees.select_city'))}</option>`;
			citySelect.disabled = true;
			areaSelect.innerHTML =
				`<option value="">${@json(__('locations_fees.select_area'))}</option>`;
			areaSelect.disabled = true;
			if (areaRow) areaRow.style.display = 'none';
			resetFee();
			return;
		}

		const result = await fetchJson(
			`${routes.cities}?governorate_id=${encodeURIComponent(governorateId)}`
		);
		setSelectOptions(citySelect, result.data || [], @json(__(
			'locations_fees.select_city')), selectedCityId || '');
		citySelect.disabled = false;

		if (selectedCityId) {
			await loadAreas(selectedCityId, oldAreaId, oldCustomArea);
		} else {
			areaSelect.innerHTML =
				`<option value="">${@json(__('locations_fees.select_area'))}</option>`;
			areaSelect.disabled = true;
			if (areaRow) areaRow.style.display = 'none';
			resetFee();
		}
	}

	async function loadAreas(cityId, selectedAreaId, customAreaValue) {
		if (!cityId) {
			areaSelect.innerHTML =
				`<option value="">${@json(__('locations_fees.select_area'))}</option>`;
			areaSelect.disabled = true;
			if (areaRow) areaRow.style.display = 'none';
			resetFee();
			return;
		}

		const result = await fetchJson(`${routes.areas}?city_id=${encodeURIComponent(cityId)}`);
		areaSelect.innerHTML =
			`<option value="">${@json(__('locations_fees.select_area'))}</option>`;
		(result.data || []).forEach((item) => {
			const opt = document.createElement('option');
			opt.value = String(item.id);
			opt.textContent = item.name;
			if (String(selectedAreaId) === String(item.id)) {
				opt.selected = true;
			}
			areaSelect.appendChild(opt);
		});

		const otherOpt = document.createElement('option');
		otherOpt.value = AREA_OTHER;
		otherOpt.textContent = @json(__('locations_fees.area_other_option'));
		if (!selectedAreaId && customAreaValue) {
			otherOpt.selected = true;
		}
		areaSelect.appendChild(otherOpt);

		areaSelect.disabled = false;
		if (areaRow) areaRow.style.display = 'grid';

		const isOther = areaSelect.value === AREA_OTHER;
		if (customAreaWrap) customAreaWrap.style.display = isOther ? 'block' : 'none';
		if (!isOther && customAreaInput) customAreaInput.value = '';

		await updateFee();
	}

	governorateSelect?.addEventListener('change', async function() {
		hiddenGovernorateId.value = this.value;
		hiddenCityId.value = '';
		hiddenAreaId.value = '';
		if (hiddenState) hiddenState.value = '';
		if (hiddenCity) hiddenCity.value = '';
		if (hiddenArea) hiddenArea.value = '';
		await loadCities(this.value, '');
	});

	citySelect?.addEventListener('change', async function() {
		hiddenCityId.value = this.value;
		hiddenAreaId.value = '';
		if (hiddenCity) hiddenCity.value = this.options[this.selectedIndex]
			?.text || '';
		if (hiddenArea) hiddenArea.value = '';
		await loadAreas(this.value, '', '');
	});

	areaSelect?.addEventListener('change', async function() {
		const isOther = this.value === AREA_OTHER;
		if (customAreaWrap) customAreaWrap.style.display = isOther ? 'block' :
			'none';
		if (!isOther && customAreaInput) customAreaInput.value = '';
		hiddenAreaId.value = isOther ? '' : this.value;
		if (hiddenArea && !isOther) {
			hiddenArea.value = this.options[this.selectedIndex]?.text ||
				'';
		}
		await updateFee();
	});

	customAreaInput?.addEventListener('input', function() {
		if (areaSelect?.value === AREA_OTHER) {
			if (hiddenArea) hiddenArea.value = this.value;
			updateFee();
		}
	});

	loadGovernorates().catch(function() {
		resetFee();
	});
}
@endif
</script>
@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function() {
	document.querySelector('.checkout-validation-summary')?.scrollIntoView({
		behavior: 'smooth',
		block: 'start'
	});
});
</script>
@endif
@endsection