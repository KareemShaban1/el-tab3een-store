@extends('frontend.store.theme_layout')

@push('styles')
<style>
    .auth-card {
        padding: 24px;
margin: 20px auto;
    }
</style>
@endpush
@section('content')
<div class="auth-card">
    <h2 class="auth-title">تسجيل دخول العميل</h2>
    <p class="auth-sub">سجّل دخولك لمتابعة الطلبات وإتمام الشراء بسهولة.</p>

    @include('frontend.store.auth.partials.validation_errors')

    <form method="POST" action="{{ route('store.auth.login') }}" novalidate>
        @csrf
        <label for="login-email">البريد الإلكتروني أو رقم الجوال</label>
        <input id="login-email" type="text" name="email" value="{{ old('email') }}" placeholder="example@email.com أو 05xxxxxxxx" autocomplete="username" class="@error('email') auth-input-error @enderror" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>

        <label for="login-password">كلمة المرور</label>
        <input id="login-password" type="password" name="password" autocomplete="current-password" class="@error('password') auth-input-error @enderror" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>

        <label style="display:flex;gap:8px;align-items:center;font-weight:600;">
            <input type="checkbox" name="remember" value="1" style="width:auto;margin:0;"> تذكرني
        </label>

        <div class="actions">
            <button class="btn btn-primary" type="submit">تسجيل الدخول</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.register.form') }}">إنشاء حساب</a>
            <a class="btn btn-ghost" href="{{ route('store.auth.password.request') }}">نسيت كلمة المرور</a>
        </div>
    </form>
</div>
@endsection
