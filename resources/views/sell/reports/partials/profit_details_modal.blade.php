<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @if ($type === 'user')
                    @lang('lang_v1.profit_by_user'):
                @else
                    @lang('lang_v1.profit_by_service_staff'):
                @endif
                {{ $person_name }}
            </h4>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="profit_person_details_table"
                    data-type="{{ $type }}"
                    data-user-id="{{ $user_id }}">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.product')</th>
                            <th>@lang('lang_v1.quantity')</th>
                            <th>@lang('sale.unit_price')</th>
                            <th>@lang('lang_v1.total_purchase_price')</th>
                            <th>@lang('sale.total')</th>
                            <th>@lang('lang_v1.gross_profit')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total">
                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_purchase_total"></td>
                            <td class="footer_sell_total"></td>
                            <td class="footer_line_profit"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
