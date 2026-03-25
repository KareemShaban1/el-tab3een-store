@extends('frontend.store.theme_layout')

@section('content')
<div class="auth-card">
    <h2 class="auth-title">إنشاء حساب عميل</h2>
    <p class="auth-sub">أنشئ حسابك للشراء وتتبع الطلبات واستلام العروض.</p>
    <form method="POST" action="{{ route('store.auth.register') }}">
        @csrf
        <div class="row">
            <div>
                <label>الاسم</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label>رقم الجوال</label>
                <input type="text" name="mobile" value="{{ old('mobile') }}">
            </div>
        </div>

        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <div class="row">
            <div>
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">تسجيل الحساب</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">لديك حساب بالفعل</a>
        </div>
    </form>
</div>
@endsection

