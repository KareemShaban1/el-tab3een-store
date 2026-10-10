{{-- Shared advanced discount fields (create/edit) --}}
@php
  $d = $discount ?? null;
@endphp
<div class="clearfix"></div>
<div class="col-md-6" id="product_input">
  <div class="form-group">
    {!! Form::label('product_id', __('lang_v1.product_all_variations') . ':') !!}
    <select name="product_id" id="product_id" class="form-control" style="width:100%;">
      <option value="">@lang('messages.please_select')</option>
      @foreach(($products ?? []) as $pid => $pname)
        <option value="{{ $pid }}"
          @if(!empty($d->product_id) && (int)$d->product_id === (int)$pid) selected @endif
          data-image="{{ ($product_images[$pid] ?? null) ?: asset('/img/default.png') }}">
          {{ $pname }}
        </option>
      @endforeach
    </select>
    <p class="help-block">@lang('lang_v1.product_all_variations_help')</p>
  </div>
</div>
<div class="col-md-6">
  <div class="form-group"><br>
    <label>
      {!! Form::checkbox('apply_on_all_variations', 1, !empty($d->apply_on_all_variations) || empty($d), ['class' => 'input-icheck']) !!}
      <strong>@lang('lang_v1.apply_on_all_variations')</strong>
    </label>
    <br>
    <label>
      {!! Form::checkbox('include_sub_categories', 1, !empty($d->include_sub_categories), ['class' => 'input-icheck']) !!}
      <strong>@lang('lang_v1.include_sub_categories')</strong>
    </label>
  </div>
</div>
<div class="clearfix"></div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('discount_kind', __('lang_v1.discount_kind') . ':') !!}
    {!! Form::select('discount_kind', ['standard' => __('lang_v1.standard'), 'flash_sale' => __('lang_v1.flash_sale')], $d->discount_kind ?? 'standard', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
  </div>
</div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('cg_mode', __('lang_v1.cg_mode') . ':') !!}
    {!! Form::select('cg_mode', [
        'any' => __('lang_v1.cg_mode_any'),
        'with_group_only' => __('lang_v1.cg_mode_with_group_only'),
        'specific_groups' => __('lang_v1.cg_mode_specific_groups'),
        'exclude_groups' => __('lang_v1.cg_mode_exclude_groups'),
    ], $d->cg_mode ?? 'any', ['class' => 'form-control select2', 'id' => 'cg_mode', 'style' => 'width:100%']) !!}
  </div>
</div>
<div class="col-md-4" id="customer_groups_wrap">
  <div class="form-group">
    {!! Form::label('customer_group_ids', __('lang_v1.customer_groups') . ':') !!}
    {!! Form::select('customer_group_ids[]', $customer_groups ?? [], isset($d) ? $d->customerGroups->pluck('id')->toArray() : [], ['class' => 'form-control select2', 'multiple', 'style' => 'width:100%', 'id' => 'customer_group_ids']) !!}
  </div>
</div>
<div class="clearfix"></div>
<div class="col-md-3">
  <div class="form-group"><br>
    <label>{!! Form::checkbox('apply_in_pos', 1, !isset($d) || !empty($d->apply_in_pos), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.apply_in_pos')</strong></label>
  </div>
</div>
<div class="col-md-3">
  <div class="form-group"><br>
    <label>{!! Form::checkbox('apply_in_ecommerce', 1, !isset($d) || !empty($d->apply_in_ecommerce), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.apply_in_ecommerce')</strong></label>
  </div>
</div>
<div class="col-md-3">
  <div class="form-group"><br>
    <label>{!! Form::checkbox('requires_promo_code', 1, !empty($d->requires_promo_code), ['class' => 'input-icheck', 'id' => 'requires_promo_code']) !!} <strong>@lang('lang_v1.requires_promo_code')</strong></label>
    <p class="help-block">@lang('lang_v1.requires_promo_code_help')</p>
  </div>
</div>
<div class="col-md-3">
  <div class="form-group">
    {!! Form::label('promo_application', __('lang_v1.promo_application') . ':') !!}
    {!! Form::select('promo_application', ['eligible_lines' => __('lang_v1.eligible_lines'), 'cart_wide' => __('lang_v1.cart_wide')], $d->promo_application ?? 'eligible_lines', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
  </div>
</div>
<div class="clearfix"></div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('min_cart_subtotal', __('lang_v1.min_cart_subtotal')) !!}
    {!! Form::text('min_cart_subtotal', $d->min_cart_subtotal ?? null, ['class' => 'form-control input_number']) !!}
  </div>
</div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('max_discount_amount', __('lang_v1.max_discount_amount')) !!}
    {!! Form::text('max_discount_amount', $d->max_discount_amount ?? null, ['class' => 'form-control input_number']) !!}
  </div>
</div>
<div class="col-md-4">
  <div class="form-group"><br>
    <label>{!! Form::checkbox('first_order_only', 1, !empty($d->first_order_only), ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.first_order_only')</strong></label>
  </div>
</div>
<div class="clearfix"></div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('flash_quota_qty', __('lang_v1.flash_quota_qty')) !!}
    {!! Form::text('flash_quota_qty', $d->flash_quota_qty ?? null, ['class' => 'form-control input_number']) !!}
  </div>
</div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('max_qty_per_order', __('lang_v1.max_qty_per_order')) !!}
    {!! Form::text('max_qty_per_order', $d->max_qty_per_order ?? null, ['class' => 'form-control input_number']) !!}
  </div>
</div>
<div class="col-md-4">
  <div class="form-group">
    {!! Form::label('sort_order', __('lang_v1.sort_order')) !!}
    {!! Form::text('sort_order', $d->sort_order ?? 0, ['class' => 'form-control input_number']) !!}
  </div>
</div>
<div class="col-md-6">
  <div class="form-group">
    {!! Form::label('banner_title', __('lang_v1.banner_title')) !!}
    {!! Form::text('banner_title', $d->banner_title ?? null, ['class' => 'form-control']) !!}
  </div>
</div>
