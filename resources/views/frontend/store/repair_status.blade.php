@extends('frontend.store.theme_layout')

@push('styles')
<style>
    .repair-status-wrap {
        max-width: 920px;
        margin: 0 auto;
        padding: 24px 16px 40px;
    }

    .repair-status-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-sm);
        padding: 24px;
        margin-bottom: 20px;
    }

    .repair-status-title {
        margin: 0 0 6px;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--primary);
    }

    .repair-status-sub {
        margin: 0 0 20px;
        color: var(--muted);
        font-size: 0.9375rem;
        line-height: 1.6;
    }

    .repair-search-grid {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 12px;
    }

    .repair-field {
        display: grid;
        gap: 6px;
        margin-bottom: 14px;
    }

    .repair-field label {
        font-size: 0.875rem;
        font-weight: 700;
        color: #374151;
    }

    .repair-field select,
    .repair-field input {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.9375rem;
        background: #fff;
    }

    .repair-field select:focus,
    .repair-field input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(234, 84, 26, 0.12);
    }

    .repair-search-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .repair-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 11px 16px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        text-decoration: none;
        font-weight: 700;
    }

    .repair-btn-secondary:hover {
        background: var(--bg-soft);
    }

    .repair-status-results {
        display: grid;
        gap: 16px;
    }

    .repair-status-loading {
        display: none;
        align-items: center;
        gap: 10px;
        color: var(--muted);
        font-size: 0.9rem;
        margin-top: 12px;
    }

    .repair-status-loading.is-visible {
        display: flex;
    }

    @media (max-width: 640px) {
        .repair-search-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="repair-status-wrap">
    <div class="repair-status-card">
        <h1 class="repair-status-title">{{ __('repair::lang.repair_status') }}</h1>
        <p class="repair-status-sub">{{ __('storefront.repair_status.subtitle') }}</p>

        <form id="check-repair-status-form" method="POST" action="{{ route('post-repair-status') }}">
            @csrf
            @php
                $search_options = [
                    'job_sheet_no' => __('repair::lang.job_sheet_no'),
                    'invoice_no' => __('sale.invoice_no'),
                ];
                $placeholder = __('repair::lang.job_sheet_or_invoice_no');
                if (config('repair.enable_repair_check_using_mobile_num')) {
                    $search_options['mobile_num'] = __('lang_v1.mobile_number');
                    $placeholder .= ' / ' . __('lang_v1.mobile_number');
                }
            @endphp

            <div class="repair-field">
                <label for="repair-search-type">{{ __('storefront.repair_status.search_type') }}</label>
                <div class="repair-search-grid">
                    <select id="repair-search-type" name="search_type" required>
                        @foreach ($search_options as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input id="repair-search-number" type="text" name="search_number" required placeholder="{{ $placeholder }}">
                </div>
            </div>

            <div class="repair-field">
                <label for="repair-serial-no">{{ __('repair::lang.serial_no') }}</label>
                <input id="repair-serial-no" type="text" name="serial_no" placeholder="{{ __('repair::lang.serial_no') }}">
            </div>

            <div class="repair-search-actions">
                <button class="btn" type="submit" id="repair-search-btn">{{ __('lang_v1.search') }}</button>
                <a href="{{ route('store.home') }}" class="repair-btn-secondary">{{ __('storefront.repair_status.back_to_store') }}</a>
            </div>

            <div class="repair-status-loading" id="repair-status-loading" aria-live="polite">
                <span>{{ __('storefront.repair_status.searching') }}</span>
            </div>
        </form>
    </div>

    <div class="repair-status-results" id="repair-status-results"></div>
</div>

<script>
window.REPAIR_STATUS_MSG = {
    results_found: @json(__('storefront.repair_status.results_found')),
    no_results: @json(__('storefront.repair_status.no_results')),
    search_error: @json(__('storefront.repair_status.search_error')),
};
(function () {
    const form = document.getElementById('check-repair-status-form');
    const resultsWrap = document.getElementById('repair-status-results');
    const loadingEl = document.getElementById('repair-status-loading');
    const submitBtn = document.getElementById('repair-search-btn');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!form || !resultsWrap) return;

        const formData = new FormData(form);
        loadingEl?.classList.add('is-visible');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: new URLSearchParams(formData),
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (result.success) {
                resultsWrap.innerHTML = result.repair_html || '';
                if (typeof toast === 'function') {
                    toast(result.msg || REPAIR_STATUS_MSG.results_found);
                }
            } else {
                resultsWrap.innerHTML = '';
                if (typeof toast === 'function') {
                    toast(result.msg || REPAIR_STATUS_MSG.no_results, 'error');
                }
            }
        } catch (err) {
            resultsWrap.innerHTML = '';
            if (typeof toast === 'function') {
                toast(REPAIR_STATUS_MSG.search_error, 'error');
            }
        } finally {
            loadingEl?.classList.remove('is-visible');
            if (submitBtn) submitBtn.disabled = false;
        }
    });
})();
</script>
@endsection
