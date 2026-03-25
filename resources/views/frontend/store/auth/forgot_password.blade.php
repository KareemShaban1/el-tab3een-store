@extends('frontend.store.theme_layout')

@section('content')
<div class="auth-card">
    <h2 class="auth-title">نسيت كلمة المرور</h2>
    <p class="auth-sub">أدخل البريد الإلكتروني لحساب العميل وسنرسل لك رابط إعادة تعيين كلمة المرور.</p>
    <form method="POST" action="{{ route('store.auth.password.email') }}">
        @csrf
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <div class="actions">
            <button class="btn btn-primary" type="submit">إرسال رابط الاستعادة</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">العودة لتسجيل الدخول</a>
        </div>
    </form>
</div>
@endsection

