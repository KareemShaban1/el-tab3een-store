<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\LocationsFees\AreaController::class, 'update'], [$area->id]), 'method' => 'PUT', 'id' => 'lf_area_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('locations_fees.areas')</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('city_id', __('locations_fees.city') . ':*') !!}
                <select name="city_id" class="form-control select2" required style="width:100%">
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected($area->city_id == $city->id)>{{ optional($city->governorate)->name }} — {{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                {!! Form::label('name', __('locations_fees.area') . ':*') !!}
                {!! Form::text('name', $area->name, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('delivery_cost', __('locations_fees.area_delivery_cost') . ':*') !!}
                {!! Form::text('delivery_cost', $area->delivery_cost, ['class' => 'form-control input_number', 'required']) !!}
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="is_active" value="1" @checked($area->is_active)> @lang('business.is_active')
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
