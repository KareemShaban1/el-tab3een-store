<div class="modal-dialog" role="document">
    {!! Form::open(['url' => action([\App\Http\Controllers\SellController::class, 'updateEcommerceStatus'], [$transaction->id]), 'method' => 'post', 'id' => 'edit_ecommerce_status_form' ]) !!}
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Edit E-commerce Status - {{ $transaction->invoice_no }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('ecommerce_order_status', 'Order Status:') !!}
                        {!! Form::select('ecommerce_order_status', $ecommerce_order_statuses, !empty($transaction->ecommerce_order_status) ? $transaction->ecommerce_order_status : (!empty($transaction->sub_status) ? str_replace('ecommerce_', '', $transaction->sub_status) : null), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('payment_status', __('sale.payment_status') . ':') !!}
                        {!! Form::select('payment_status', $payment_statuses, !empty($transaction->payment_status) ? $transaction->payment_status : null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':') !!}
                        {!! Form::select('shipping_status', $shipping_statuses, !empty($transaction->shipping_status) ? $transaction->shipping_status : null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':') !!}
                        {!! Form::text('delivered_to', !empty($transaction->delivered_to) ? $transaction->delivered_to : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.delivered_to')]) !!}
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('shipping_details', __('sale.shipping_details') . ':') !!}
                        {!! Form::textarea('shipping_details', !empty($shipping_details_prefill) ? $shipping_details_prefill : '', ['class' => 'form-control', 'rows' => '3']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.cancel')</button>
        </div>
    </div>
    {!! Form::close() !!}
</div>

