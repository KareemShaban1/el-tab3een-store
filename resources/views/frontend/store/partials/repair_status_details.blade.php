@foreach ($sells as $sell)
    <div class="repair-result-card">
        <div class="repair-result-header">
            <div>
                <p class="repair-result-label">{{ __('repair::lang.job_sheet_no') }}</p>
                <h3 class="repair-result-title">{{ $sell->job_sheet_no }}</h3>
            </div>
            <span class="repair-status-badge" style="background-color: {{ $sell->repair_status_color }};">
                {{ $sell->repair_status }}
            </span>
        </div>

        <div class="repair-result-grid">
            <div class="repair-result-item">
                <span class="repair-result-item-label">@lang('product.brand')</span>
                <span class="repair-result-item-value">{{ $sell->manufacturer ?: __('storefront.repair_status.not_available') }}</span>
            </div>
            <div class="repair-result-item">
                <span class="repair-result-item-label">@lang('repair::lang.device')</span>
                <span class="repair-result-item-value">{{ $sell->repair_device ?: __('storefront.repair_status.not_available') }}</span>
            </div>
            <div class="repair-result-item">
                <span class="repair-result-item-label">@lang('repair::lang.model')</span>
                <span class="repair-result-item-value">{{ $sell->repair_model ?: __('storefront.repair_status.not_available') }}</span>
            </div>
            <div class="repair-result-item">
                <span class="repair-result-item-label">@lang('repair::lang.serial_no')</span>
                <span class="repair-result-item-value">{{ $sell->serial_no ?: __('storefront.repair_status.not_available') }}</span>
            </div>
            <div class="repair-result-item repair-result-item--wide">
                <span class="repair-result-item-label">@lang('repair::lang.expected_delivery_date')</span>
                <span class="repair-result-item-value">
                    @if (!empty($sell->delivery_date))
                        {{ \Carbon::parse($sell->delivery_date)->toDayDateTimeString() }}
                    @else
                        {{ __('storefront.repair_status.not_available') }}
                    @endif
                </span>
            </div>
        </div>

        <div class="repair-activities-wrap">
            <h4 class="repair-activities-title">@lang('repair::lang.activities')</h4>
            @include('frontend.store.partials.repair_status_activities', ['activities' => $sell['activities']])
        </div>
    </div>
@endforeach

<style>
    .repair-result-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-sm);
        padding: 20px;
    }

    .repair-result-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border);
    }

    .repair-result-label {
        margin: 0 0 4px;
        font-size: 0.8125rem;
        color: var(--muted);
    }

    .repair-result-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
    }

    .repair-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .repair-result-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .repair-result-item {
        background: var(--bg-soft);
        border-radius: 10px;
        padding: 12px;
    }

    .repair-result-item--wide {
        grid-column: 1 / -1;
    }

    .repair-result-item-label {
        display: block;
        font-size: 0.75rem;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .repair-result-item-value {
        display: block;
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--text);
    }

    .repair-activities-wrap {
        border-top: 1px solid var(--border);
        padding-top: 16px;
    }

    .repair-activities-title {
        margin: 0 0 12px;
        font-size: 1rem;
        font-weight: 800;
        color: var(--primary);
    }

    @media (max-width: 640px) {
        .repair-result-grid {
            grid-template-columns: 1fr;
        }

        .repair-result-header {
            flex-direction: column;
        }
    }
</style>
