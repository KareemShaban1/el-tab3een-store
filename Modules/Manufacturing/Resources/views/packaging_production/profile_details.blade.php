@if(!empty($details))
@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.packaging_calculation')])
@php
    $uses_carton = !empty($details['profile']->uses_carton);
    $bulk_consumed = !empty($details['calculation']) ? (float) $details['calculation']['bulk_consumed'] : 0;
    $bulk_available = (float) $details['bulk_stock'];
    $bulk_remaining = $bulk_available - $bulk_consumed;
@endphp
<div class="row">
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.bulk_product'):</strong> {{ $details['bulk_label'] }}</p>
        <p>
            <strong>@lang('manufacturing::lang.available_stock'):</strong>
            <span class="display_currency" data-currency_symbol="false" id="packaging_bulk_available" data-orig-value="{{ $bulk_available }}">{{ $bulk_available }}</span>
        </p>
        <div class="form-group">
            {!! Form::label('waste_quantity', __('manufacturing::lang.waste_units') . ':') !!}
            {!! Form::text('waste_quantity', old('waste_quantity', 0), [
                'class' => 'form-control input_number',
                'id' => 'waste_quantity',
                'data-bulk-consumed' => $bulk_consumed,
                'data-bulk-available' => $bulk_available,
            ]) !!}
            <p class="help-block">@lang('manufacturing::lang.packaging_waste_help')</p>
        </div>
        <p>
            <strong>@lang('manufacturing::lang.bulk_after_waste'):</strong>
            <span id="packaging_bulk_after_waste" class="display_currency label label-default" style="font-size:14px;" data-currency_symbol="false">{{ $bulk_remaining }}</span>
            <br>
            <small class="text-muted" id="packaging_bulk_formula">@lang('manufacturing::lang.bulk_after_waste_formula')</small>
        </p>
    </div>
    <div class="col-sm-4">
        <p><strong>@lang('manufacturing::lang.output_product'):</strong> {{ $details['output_label'] }}</p>
        <p>
            <strong>
                @if($uses_carton)
                    @lang('manufacturing::lang.current_carton_stock')
                @else
                    @lang('manufacturing::lang.current_output_stock')
                @endif:
            </strong>
            <span class="display_currency" data-currency_symbol="false">{{ $details['output_stock'] }}</span>
        </p>
    </div>
    <div class="col-sm-4">
        @if($uses_carton)
            <p><strong>@lang('manufacturing::lang.units_per_carton'):</strong> {{ $details['profile']->units_per_carton }}</p>
        @else
            <p><strong>@lang('manufacturing::lang.packaging_mode'):</strong> @lang('manufacturing::lang.containers_only')</p>
        @endif
        <p><strong>@lang('manufacturing::lang.container_type'):</strong> @lang('manufacturing::lang.' . $details['profile']->container_type)</p>
    </div>
</div>

@if(!empty($details['calculation']))
    <hr>
    <div class="row">
        @if($uses_carton)
            <div class="col-sm-3">
                <p><strong>@lang('manufacturing::lang.cartons'):</strong> {{ $details['calculation']['full_cartons'] }}</p>
            </div>
            <div class="col-sm-3">
                <p><strong>@lang('manufacturing::lang.leftover_containers'):</strong> {{ $details['calculation']['leftover_containers'] }}</p>
            </div>
        @else
            <div class="col-sm-3">
                <p><strong>@lang('manufacturing::lang.output_quantity'):</strong> <span class="display_currency" data-currency_symbol="false" id="packaging_output_quantity">{{ $details['calculation']['output_quantity'] }}</span></p>
            </div>
        @endif
        <div class="col-sm-3">
            <p><strong>@lang('manufacturing::lang.bulk_consumed'):</strong> <span class="display_currency" data-currency_symbol="false" id="packaging_bulk_consumed" data-orig-value="{{ $details['calculation']['bulk_consumed'] }}">{{ $details['calculation']['bulk_consumed'] }}</span></p>
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
                <td><span class="display_currency" data-currency_symbol="false" id="packaging_bulk_required">{{ $details['calculation']['bulk_consumed'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="false">{{ $details['bulk_stock'] }}</span></td>
            </tr>
            @foreach($details['calculation']['materials'] as $material)
                @continue($material['quantity'] <= 0)
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
