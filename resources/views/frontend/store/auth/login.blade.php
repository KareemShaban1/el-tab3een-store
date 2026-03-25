@extends('frontend.store.theme_layout')

@section('content')
<div class="auth-card">
    <h2 class="auth-title">تسجيل دخول العميل</h2>
    <p class="auth-sub">سجّل دخولك لمتابعة الطلبات وإتمام الشراء بسهولة.</p>
    <form method="POST" action="{{ route('store.auth.login') }}">
        @csrf
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label>كلمة المرور</label>
        <input type="password" name="password" required>

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

