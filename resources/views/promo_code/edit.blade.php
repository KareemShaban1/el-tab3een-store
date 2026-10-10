<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\PromoCodeController::class, 'update'], [$promo_code->id]), 'method' => 'put', 'id' => 'promo_code_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.edit_promo_code')</h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('code', __('lang_v1.promo_code') . ':*') !!}
            {!! Form::text('code', $promo_code->code, ['class' => 'form-control', 'required', 'style' => 'text-transform:uppercase']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('discount_id', __('sale.discount') . ':*') !!}
            {!! Form::select('discount_id', $discounts, $promo_code->discount_id, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            {!! Form::label('description', __('lang_v1.description')) !!}
            {!! Form::text('description', $promo_code->description, ['class' => 'form-control']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('starts_at', __('lang_v1.starts_at')) !!}
            {!! Form::text('starts_at', $starts_at, ['class' => 'form-control discount_date', 'readonly']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('ends_at', __('lang_v1.ends_at')) !!}
            {!! Form::text('ends_at', $ends_at, ['class' => 'form-control discount_date', 'readonly']) !!}
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('max_uses_total', __('lang_v1.max_uses_total')) !!}
            {!! Form::number('max_uses_total', $promo_code->max_uses_total, ['class' => 'form-control', 'min' => 1]) !!}
            <p class="help-block">@lang('lang_v1.used_count'): {{ $promo_code->used_count }}</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('max_uses_per_customer', __('lang_v1.max_uses_per_customer')) !!}
            {!! Form::number('max_uses_per_customer', $promo_code->max_uses_per_customer, ['class' => 'form-control', 'min' => 1]) !!}
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group"><br>
            <label>{!! Form::checkbox('is_single_use', 1, !empty($promo_code->is_single_use), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.is_single_use')</strong></label><br>
            <label>{!! Form::checkbox('restore_on_return', 1, !empty($promo_code->restore_on_return), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.restore_on_return')</strong></label><br>
            <label>{!! Form::checkbox('is_active', 1, !empty($promo_code->is_active), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.is_active')</strong></label>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
