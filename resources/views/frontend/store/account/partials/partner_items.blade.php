@if (! empty($servoItems))
    <div class="card">
        <h3 class="section-title">{{ __('storefront.orders.partner_section') }}</h3>

        <table class="items-table" style="text-align: right; width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:12px;text-transform:uppercase;">{{ __('lang_v1.item') }}</th>
                    <th class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:12px;text-transform:uppercase;">{{ __('lang_v1.qty') }}</th>
                    <th class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:12px;text-transform:uppercase;">{{ __('lang_v1.unit_price') }}</th>
                    <th class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:12px;text-transform:uppercase;">{{ __('lang_v1.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servoItems as $item)
                    <tr>
                        <td class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;text-align:right !important;">
                            <div><strong>{{ $item['product_name'] }}</strong></div>
                            @if (! empty($item['variation_name']))
                                <div class="muted" style="color:#6b7280;font-size:12px;">{{ $item['variation_name'] }}</div>
                            @endif
                        </td>
                        <td class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;text-align:right !important;">{{ number_format((float) $item['quantity'], 2) }}</td>
                        <td class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;text-align:right !important;">
                            @if ($item['unit_price'] !== null)
                                {{ number_format((float) $item['unit_price'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="txt-right" style="padding:10px 8px;border-bottom:1px solid #f1f5f9;text-align:right !important;">
                            @if ($item['line_total'] !== null)
                                {{ number_format((float) $item['line_total'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (! empty($servoOrders) && $servoOrders->isNotEmpty())
            @foreach ($servoOrders as $servoOrder)
                @if (! empty($servoOrder->error_message) && $servoOrder->status === 'failed')
                    <p class="muted" style="margin:12px 0 0;color:#6b7280;font-size:12px;">
                        <strong>{{ __('storefront.orders.error_message') }}:</strong> {{ $servoOrder->error_message }}
                    </p>
                @endif
            @endforeach
        @endif
    </div>
@endif
