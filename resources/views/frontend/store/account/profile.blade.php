@extends('frontend.store.theme_layout')

@section('content')
<style>
    .profile-wrap { display: grid; gap: 14px; padding: 30px; }
    .profile-title { margin: 0; font-size: 24px; }
    .profile-sub { margin: 6px 0 0; color: #6b7280; font-size: 14px; }
    .profile-card { max-width: 860px; }
    .section-block { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #fff; margin-bottom: 12px; }
    .section-title { margin: 0 0 10px; font-size: 16px; }
    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .field { display: grid; gap: 6px; }
    .field label { font-size: 13px; color: #374151; font-weight: 700; }
    .field small { color: #6b7280; }
    .form-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-top: 6px; }
    .btn-soft { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 12px; color: #111827; text-decoration: none; font-weight: 600; background: #fff; }
    .btn-soft:hover { background: #f8fafc; }
    @media (max-width: 900px) { .field-grid { grid-template-columns: 1fr; } }
</style>

<div class="container profile-wrap">
<div class="card profile-card">
    <h2 class="profile-title"> {{ __('lang_v1.my_profile') }}</h2>
    <p class="profile-sub"> {{ __('lang_v1.keep_your_contact_and_shipping_information_up_to_date_for_faster_checkout') }}</p>
</div>

<div class="card profile-card">
    <form method="POST" action="{{ route('store.account.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="section-block">
            <h3 class="section-title"> {{ __('lang_v1.basic_information') }}</h3>
            <div class="field-grid">
                <div class="field">
                    <label for="profile_name"> {{ __('lang_v1.name') }}</label>
                    <input id="profile_name" type="text" name="name" value="{{ old('name', $customer->name) }}" required>
                </div>
                <div class="field">
                    <label for="profile_mobile"> {{ __('lang_v1.mobile') }}</label>
                    <input id="profile_mobile" type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}">
                </div>
            </div>
        </div>

        <div class="section-block">
            <h3 class="section-title"> {{ __('lang_v1.shipping_address') }}</h3>
            <div class="field">
                <label for="shipping_address"> {{ __('lang_v1.address') }}</label>
                <textarea id="shipping_address" name="shipping_address" rows="3">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                <small> {{ __('lang_v1.this_address_will_be_used_as_your_default_shipping_address') }}</small>
            </div>

            <div class="field-grid" style="margin-top:12px;">
                <div class="field">
                    <label for="profile_city"> {{ __('lang_v1.city') }}</label>
                    <input id="profile_city" type="text" name="city" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="field">
                    <label for="profile_state"> {{ __('lang_v1.state') }}</label>
                    <input id="profile_state" type="text" name="state" value="{{ old('state', $customer->state) }}">
                </div>
                <!-- <div class="field">
                    <label for="profile_country"> {{ __('lang_v1.country') }}</label>
                    <input id="profile_country" type="text" name="country" value="{{ old('country', $customer->country) }}">
                </div> -->
                <!-- <div class="field">
                    <label for="profile_zip"> {{ __('lang_v1.zip_code') }}</label>
                    <input id="profile_zip" type="text" name="zip_code" value="{{ old('zip_code', $customer->zip_code) }}">
                </div> -->
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('store.account.orders') }}" class="btn-soft"> {{ __('lang_v1.my_orders') }}</a>
            <button class="btn" type="submit"> {{ __('lang_v1.save_profile') }}</button>
        </div>
    </form>
</div>
</div>
@endsection

