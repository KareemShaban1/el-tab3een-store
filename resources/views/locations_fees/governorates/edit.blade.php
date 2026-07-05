<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\LocationsFees\GovernorateController::class, 'update'], [$governorate->id]), 'method' => 'PUT', 'id' => 'lf_governorate_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('locations_fees.governorates')</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('locations_fees.governorate') . ':*') !!}
                {!! Form::text('name', $governorate->name, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="is_active" value="1" @checked($governorate->is_active)> @lang('business.is_active')
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
