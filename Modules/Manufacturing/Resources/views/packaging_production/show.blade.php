<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('manufacturing::lang.packaging_production') — {{ $production->ref_no }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <p><strong>@lang('messages.date'):</strong> {{ @format_datetime($production->transaction_date) }}</p>
                    <p><strong>@lang('purchase.business_location'):</strong> {{ $production->location->name ?? '' }}</p>
                    <p><strong>@lang('manufacturing::lang.profile_name'):</strong> {{ $profile->name ?? '' }}</p>
                </div>
                <div class="col-sm-6">
                    <p><strong>@lang('manufacturing::lang.containers'):</strong> {{ $production->mfg_containers_count }} @lang('manufacturing::lang.' . ($production->mfg_container_type == 'bag' ? 'bags' : 'bottles'))</p>
                    <p><strong>@lang('manufacturing::lang.cartons'):</strong> {{ $production->mfg_cartons_count }}</p>
                    <p><strong>@lang('sale.total'):</strong> <span class="display_currency" data-currency_symbol="true">{{ $production->final_total }}</span></p>
                </div>
            </div>

            @if(!empty($production_sell))
                <hr>
                <h4>@lang('manufacturing::lang.consumed_items')</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('lang_v1.quantity')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($production_sell->sell_lines as $line)
                            <tr>
                                <td>{{ $line->variations->full_name ?? '' }}</td>
                                <td>{{ $line->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
