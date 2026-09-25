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
    <h2 class="auth-title">نسيت كلمة المرور</h2>
    <p class="auth-sub">أدخل البريد الإلكتروني أو رقم الجوال المسجّل، وسنرسل لك رابط إعادة تعيين كلمة المرور.</p>

    @include('frontend.store.auth.partials.validation_errors')

    <form method="POST" action="{{ route('store.auth.password.email') }}" novalidate>
        @csrf
        <label for="forgot-email">البريد الإلكتروني أو رقم الجوال</label>
        <input id="forgot-email" type="text" name="email" value="{{ old('email') }}" placeholder="example@email.com أو 05xxxxxxxx" autocomplete="username" class="@error('email') auth-input-error @enderror" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>

        <div class="actions">
            <button class="btn btn-primary" type="submit">إرسال رابط الاستعادة</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">العودة لتسجيل الدخول</a>
        </div>
    </form>
</div>
@endsection
