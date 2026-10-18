<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('manufacturing::lang.transformation_details')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <p><strong>@lang('purchase.ref_no'):</strong> {{ $transformation->ref_no }}</p>
                    <p><strong>@lang('messages.date'):</strong> {{ @format_datetime($transformation->transformed_at) }}</p>
                    <p><strong>@lang('purchase.business_location'):</strong> {{ $transformation->location->name ?? '' }}</p>
                    <p><strong>@lang('manufacturing::lang.profile_name'):</strong> {{ $transformation->profile->name ?? '' }}</p>
                </div>
                <div class="col-sm-6">
                    <p><strong>@lang('manufacturing::lang.carton_product'):</strong> {{ \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($transformation->cartonVariation) }}</p>
                    <p><strong>@lang('manufacturing::lang.carton_qty'):</strong> {{ $transformation->carton_qty }}</p>
                    <p><strong>@lang('manufacturing::lang.unit_product'):</strong> {{ \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($transformation->unitVariation) }}</p>
                    <p><strong>@lang('manufacturing::lang.unit_qty'):</strong> {{ $transformation->unit_qty }}</p>
                    <p><strong>@lang('manufacturing::lang.units_per_carton'):</strong> {{ $transformation->units_per_carton }}</p>
                </div>
            </div>
            @if(!empty($transformation->notes))
                <p><strong>@lang('purchase.additional_notes'):</strong> {{ $transformation->notes }}</p>
            @endif
            @if(!empty($transformation->createdBy))
                <p><strong>@lang('lang_v1.added_by'):</strong> {{ $transformation->createdBy->user_full_name }}</p>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
