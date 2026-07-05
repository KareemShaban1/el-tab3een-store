<div class="repair-activities-table-wrap">
    <table class="repair-activities-table">
        <thead>
            <tr>
                <th>@lang('lang_v1.date')</th>
                <th>@lang('messages.action')</th>
                <th>@lang('lang_v1.by')</th>
                <th>@lang('brand.note')</th>
            </tr>
        </thead>
        <tbody>
            @php $has_rows = false; @endphp
            @foreach ($activities as $activity)
                @if ($activity->description != 'is_sent_notification')
                    @php $has_rows = true; @endphp
                    <tr>
                        <td>{{ $activity->created_at->toDayDateTimeString() }}</td>
                        <td>
                            @if ($activity->description == 'status_changed')
                                @lang('repair::lang.status_changed_to', ['status' => $activity->getExtraProperty('updated_status')])
                            @else
                                {{ __('lang_v1.' . $activity->description) }}
                            @endif
                        </td>
                        <td>{{ optional($activity->causer)->user_full_name ?? __('storefront.repair_status.not_available') }}</td>
                        <td>
                            @if (!empty($activity->getExtraProperty('update_note')))
                                {{ $activity->getExtraProperty('update_note') }}<br>
                            @endif
                            @if (!empty($activity->getExtraProperty('completed_on_from')))
                                @lang('repair::lang.completed_on_changed')
                                @lang('account.from'): {{ @format_datetime($activity->getExtraProperty('completed_on_from')) }}
                                @lang('account.to'): {{ @format_datetime($activity->getExtraProperty('completed_on_to')) }}
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
            @unless ($has_rows)
                <tr>
                    <td colspan="4" class="repair-activities-empty">@lang('purchase.no_records_found')</td>
                </tr>
            @endunless
        </tbody>
    </table>
</div>

<style>
    .repair-activities-table-wrap {
        overflow-x: auto;
    }

    .repair-activities-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .repair-activities-table th,
    .repair-activities-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        text-align: right;
        vertical-align: top;
    }

    .repair-activities-table th {
        background: var(--bg-soft);
        color: var(--muted);
        font-weight: 700;
        font-size: 0.8125rem;
    }

    .repair-activities-empty {
        text-align: center;
        color: var(--muted);
        padding: 18px 12px;
    }
</style>
