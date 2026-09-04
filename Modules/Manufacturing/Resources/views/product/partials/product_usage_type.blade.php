@if(\Modules\Manufacturing\Support\PackagingFeature::isEnabledForBusiness(session()->get('user.business_id')))
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('product_usage_type', __('manufacturing::lang.product_usage_type') . ':') !!}
        @show_tooltip(__('manufacturing::lang.product_usage_type_tooltip'))
        {!! Form::select(
            'product_usage_type',
            ['' => __('messages.please_select')] + [
                'raw_ingredient' => __('manufacturing::lang.usage_raw_ingredient'),
                'bulk_finished' => __('manufacturing::lang.usage_bulk_finished'),
                'packaging_material' => __('manufacturing::lang.usage_packaging_material'),
                'packaged_finished' => __('manufacturing::lang.usage_packaged_finished'),
            ],
            !empty($product) ? $product->product_usage_type : null,
            ['class' => 'form-control select2', 'id' => 'product_usage_type']
        ) !!}
    </div>
</div>
@endif
