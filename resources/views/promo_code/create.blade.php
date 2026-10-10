<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\PromoCodeController::class, 'store']), 'method' => 'post', 'id' => 'promo_code_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.add_promo_code')</h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('code', __('lang_v1.promo_code') . ':*') !!}
            {!! Form::text('code', null, ['class' => 'form-control', 'required', 'style' => 'text-transform:uppercase']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('discount_id', __('sale.discount') . ':*') !!}
            {!! Form::select('discount_id', $discounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']) !!}
            <p class="help-block">@lang('lang_v1.promo_code_discount_help')</p>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            {!! Form::label('description', __('lang_v1.description')) !!}
            {!! Form::text('description', null, ['class' => 'form-control']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('starts_at', __('lang_v1.starts_at')) !!}
            {!! Form::text('starts_at', null, ['class' => 'form-control discount_date', 'readonly']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('ends_at', __('lang_v1.ends_at')) !!}
            {!! Form::text('ends_at', null, ['class' => 'form-control discount_date', 'readonly']) !!}
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('max_uses_total', __('lang_v1.max_uses_total')) !!}
            {!! Form::number('max_uses_total', null, ['class' => 'form-control', 'min' => 1]) !!}
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('max_uses_per_customer', __('lang_v1.max_uses_per_customer')) !!}
            {!! Form::number('max_uses_per_customer', null, ['class' => 'form-control', 'min' => 1]) !!}
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group"><br>
            <label>{!! Form::checkbox('is_single_use', 1, false, ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.is_single_use')</strong></label><br>
            <label>{!! Form::checkbox('restore_on_return', 1, true, ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.restore_on_return')</strong></label><br>
            <label>{!! Form::checkbox('is_active', 1, true, ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.is_active')</strong></label>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
