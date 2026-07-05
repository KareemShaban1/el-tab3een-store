<div class="table-responsive">
    <table class="table table-bordered table-striped tw-mb-0">
        <thead>
            <tr>
                <th>@lang('product.product_name')</th>
                <th>@lang('lang_v1.variation')</th>
                <th class="text-right">@lang('sale.qty')</th>
                <th class="text-right">@lang('lang_v1.unit_price')</th>
                <th class="text-right">@lang('lang_v1.line_total')</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['variation_name'] ?: '-' }}</td>
                    <td class="text-right">{{ @num_format($item['quantity']) }}</td>
                    <td class="text-right">
                        @if ($item['unit_price'] !== null)
                            @format_currency($item['unit_price'])
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($item['line_total'] !== null)
                            @format_currency($item['line_total'])
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">@lang('lang_v1.no_data')</td>
                </tr>
            @endforelse
        </tbody>
        @if (! empty($section_total) && $section_total > 0)
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">@lang('sale.subtotal')</th>
                    <th class="text-right">
                        @format_currency($section_total)
                    </th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
