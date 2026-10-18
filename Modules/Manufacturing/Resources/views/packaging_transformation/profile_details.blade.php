@if(!empty($details))
@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.transformation_preview')])
<div class="row">
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.carton_product'):</strong> {{ $details['carton_label'] }}</p>
        <p>
            <strong>@lang('manufacturing::lang.current_carton_stock'):</strong>
            <span class="display_currency" data-currency_symbol="false">{{ $details['carton_stock'] }}</span>
        </p>
    </div>
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.unit_product'):</strong> {{ $details['unit_label'] }}</p>
        <p>
            <strong>@lang('manufacturing::lang.current_unit_stock'):</strong>
            <span class="display_currency" data-currency_symbol="false">{{ $details['unit_stock'] }}</span>
        </p>
    </div>
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.units_per_carton'):</strong> {{ $details['profile']->units_per_carton }}</p>
        <p><strong>@lang('manufacturing::lang.container_type'):</strong> @lang('manufacturing::lang.' . $details['profile']->container_type)</p>
    </div>
</div>

@if(!empty($details['calculation']))
    <hr>
    <div class="row">
        <div class="col-sm-4">
            <p>
                <strong>@lang('manufacturing::lang.cartons_to_deduct'):</strong>
                <span class="display_currency label label-danger" style="font-size:14px;" data-currency_symbol="false">{{ $details['calculation']['carton_qty'] }}</span>
            </p>
        </div>
        <div class="col-sm-4">
            <p>
                <strong>@lang('manufacturing::lang.units_to_add'):</strong>
                <span class="display_currency label label-success" style="font-size:14px;" data-currency_symbol="false">{{ $details['calculation']['unit_qty'] }}</span>
            </p>
        </div>
        <div class="col-sm-4">
            @if($details['carton_stock'] < $details['calculation']['carton_qty'])
                <p class="text-danger"><strong>@lang('manufacturing::lang.insufficient_carton_stock_for_transformation')</strong></p>
            @else
                <p class="text-success"><strong>@lang('manufacturing::lang.stock_available_for_transformation')</strong></p>
            @endif
        </div>
    </div>
@endif
@endcomponent
@endif
