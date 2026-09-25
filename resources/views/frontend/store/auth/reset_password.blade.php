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
    <h2 class="auth-title">إعادة تعيين كلمة المرور</h2>
    <p class="auth-sub">اختر كلمة مرور جديدة وآمنة لحسابك.</p>

    @include('frontend.store.auth.partials.validation_errors')

    <form method="POST" action="{{ route('store.auth.password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        @if ($showEmail)
            <label for="reset-email">البريد الإلكتروني</label>
            <input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" class="@error('email') auth-input-error @enderror" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" autocomplete="email" readonly>
        @else
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
        @endif

        <div class="row">
            <div>
                <label for="reset-password">كلمة المرور الجديدة</label>
                <input id="reset-password" type="password" name="password" class="@error('password') auth-input-error @enderror" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" autocomplete="new-password" required>
            </div>
            <div>
                <label for="reset-password-confirmation">تأكيد كلمة المرور</label>
                <input id="reset-password-confirmation" type="password" name="password_confirmation" class="@error('password_confirmation') auth-input-error @enderror" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" autocomplete="new-password" required>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">تحديث كلمة المرور</button>
            <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">العودة لتسجيل الدخول</a>
        </div>
    </form>
</div>
@endsection
