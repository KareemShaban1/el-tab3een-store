@extends('frontend.store.theme_layout')

@section('content')
<div class="auth-card">
    <h2 class="auth-title">إعادة تعيين كلمة المرور</h2>
    <p class="auth-sub">اختر كلمة مرور جديدة وآمنة لحسابك.</p>
    <form method="POST" action="{{ route('store.auth.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email', $email) }}" required>

        <div class="row">
            <div>
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">تحديث كلمة المرور</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">العودة لتسجيل الدخول</a>
        </div>
    </form>
</div>
@endsection

