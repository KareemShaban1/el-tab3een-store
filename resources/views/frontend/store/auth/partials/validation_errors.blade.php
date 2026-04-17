@if ($errors->any())
    <div class="alert error auth-validation-summary" role="alert" aria-live="polite">
        <!-- <div class="auth-validation-summary__title">{{ __('storefront.auth.errors_summary_title') }}</div> -->
        <p class="auth-validation-summary__hint">{{ __('storefront.auth.errors_summary_hint') }}</p>
        <ul class="auth-validation-summary__list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
