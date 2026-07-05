<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\LocationsFees\CityController::class, 'update'], [$city->id]), 'method' => 'PUT', 'id' => 'lf_city_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('locations_fees.cities')</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('governorate_id', __('locations_fees.governorate') . ':*') !!}
                {!! Form::select('governorate_id', $governorates, $city->governorate_id, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('name', __('locations_fees.city') . ':*') !!}
                {!! Form::text('name', $city->name, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('delivery_cost', __('locations_fees.city_delivery_cost') . ':*') !!}
                {!! Form::text('delivery_cost', $city->delivery_cost, ['class' => 'form-control input_number', 'required']) !!}
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="is_active" value="1" @checked($city->is_active)> @lang('business.is_active')
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
