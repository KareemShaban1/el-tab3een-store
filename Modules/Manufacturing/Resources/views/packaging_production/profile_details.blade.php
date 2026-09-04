@if(!empty($details))
@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.packaging_calculation')])
<div class="row">
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.bulk_product'):</strong> {{ $details['bulk_label'] }}</p>
        <p><strong>@lang('manufacturing::lang.available_stock'):</strong> <span class="display_currency" data-currency_symbol="false">{{ $details['bulk_stock'] }}</span></p>
    </div>
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.output_product'):</strong> {{ $details['output_label'] }}</p>
        <p><strong>@lang('manufacturing::lang.current_carton_stock'):</strong> <span class="display_currency" data-currency_symbol="false">{{ $details['output_stock'] }}</span></p>
    </div>
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.units_per_carton'):</strong> {{ $details['profile']->units_per_carton }}</p>
        <p><strong>@lang('manufacturing::lang.container_type'):</strong> @lang('manufacturing::lang.' . $details['profile']->container_type)</p>
    </div>
</div>

@if(!empty($details['calculation']))
    <hr>
    <div class="row">
        <div class="col-sm-3">
            <p><strong>@lang('manufacturing::lang.cartons'):</strong> {{ $details['calculation']['full_cartons'] }}</p>
        </div>
        <div class="col-sm-3">
            <p><strong>@lang('manufacturing::lang.bulk_consumed'):</strong> <span class="display_currency" data-currency_symbol="false">{{ $details['calculation']['bulk_consumed'] }}</span></p>
        </div>
        <div class="col-sm-3">
            <p><strong>@lang('manufacturing::lang.leftover_containers'):</strong> {{ $details['calculation']['leftover_containers'] }}</p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>@lang('manufacturing::lang.material')</th>
                <th>@lang('manufacturing::lang.required_qty')</th>
                <th>@lang('manufacturing::lang.available_stock')</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $details['bulk_label'] }}</td>
                <td><span class="display_currency" data-currency_symbol="false">{{ $details['calculation']['bulk_consumed'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="false">{{ $details['bulk_stock'] }}</span></td>
            </tr>
            @foreach($details['calculation']['materials'] as $material)
                <tr @if(!empty($material['available']) && $material['available'] < $material['quantity']) class="bg-danger" @endif>
                    <td>{{ $material['full_name'] }}</td>
                    <td><span class="display_currency" data-currency_symbol="false">{{ $material['quantity'] }}</span></td>
                    <td><span class="display_currency" data-currency_symbol="false">{{ $material['available'] ?? 0 }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endcomponent
@endif
