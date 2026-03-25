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
    <h2 class="profile-title">My Profile</h2>
    <p class="profile-sub">Keep your contact and shipping information up to date for faster checkout.</p>
</div>

<div class="card profile-card">
    <form method="POST" action="{{ route('store.account.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="section-block">
            <h3 class="section-title">Basic Information</h3>
            <div class="field-grid">
                <div class="field">
                    <label for="profile_name">Name</label>
                    <input id="profile_name" type="text" name="name" value="{{ old('name', $customer->name) }}" required>
                </div>
                <div class="field">
                    <label for="profile_mobile">Mobile</label>
                    <input id="profile_mobile" type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}">
                </div>
            </div>
        </div>

        <div class="section-block">
            <h3 class="section-title">Shipping Address</h3>
            <div class="field">
                <label for="shipping_address">Address</label>
                <textarea id="shipping_address" name="shipping_address" rows="3">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                <small>This address will be used as your default shipping address.</small>
            </div>

            <div class="field-grid" style="margin-top:12px;">
                <div class="field">
                    <label for="profile_city">City</label>
                    <input id="profile_city" type="text" name="city" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="field">
                    <label for="profile_state">State</label>
                    <input id="profile_state" type="text" name="state" value="{{ old('state', $customer->state) }}">
                </div>
                <div class="field">
                    <label for="profile_country">Country</label>
                    <input id="profile_country" type="text" name="country" value="{{ old('country', $customer->country) }}">
                </div>
                <div class="field">
                    <label for="profile_zip">ZIP Code</label>
                    <input id="profile_zip" type="text" name="zip_code" value="{{ old('zip_code', $customer->zip_code) }}">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('store.account.orders') }}" class="btn-soft">My Orders</a>
            <button class="btn" type="submit">Save Profile</button>
        </div>
    </form>
</div>
</div>
@endsection

