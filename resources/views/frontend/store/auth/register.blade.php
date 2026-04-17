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
    <h2 class="auth-title">إنشاء حساب عميل</h2>
    <p class="auth-sub">أنشئ حسابك للشراء وتتبع الطلبات واستلام العروض.</p>

    @include('frontend.store.auth.partials.validation_errors')

    <form method="POST" action="{{ route('store.auth.register') }}" novalidate>
        @csrf
        <div class="row">
            <div>
                <label for="register-name">الاسم</label>
                <input id="register-name" type="text" name="name" value="{{ old('name') }}" class="@error('name') auth-input-error @enderror" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" required>
            </div>
            <div>
                <label for="register-mobile">رقم الجوال</label>
                <input id="register-mobile" type="text" name="mobile" value="{{ old('mobile') }}" class="@error('mobile') auth-input-error @enderror" aria-invalid="{{ $errors->has('mobile') ? 'true' : 'false' }}" required>
            </div>
        </div>

        <label for="register-email">البريد الإلكتروني</label>
        <input id="register-email" type="email" name="email" value="{{ old('email') }}" class="@error('email') auth-input-error @enderror" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" autocomplete="email" required>

        <div class="row">
            <div>
                <label for="register-password">كلمة المرور</label>
                <input id="register-password" type="password" name="password" class="@error('password') auth-input-error @enderror" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" autocomplete="new-password" required>
            </div>
            <div>
                <label for="register-password-confirmation">تأكيد كلمة المرور</label>
                <input id="register-password-confirmation" type="password" name="password_confirmation" class="@error('password_confirmation') auth-input-error @enderror" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" autocomplete="new-password" required>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">تسجيل الحساب</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">لديك حساب بالفعل</a>
        </div>
    </form>
</div>
@endsection
