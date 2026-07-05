<button type="button" class="btn btn-sm btn-primary btn-modal pull-right"
    data-href="{{ action([\App\Http\Controllers\LocationsFees\GovernorateController::class, 'create']) }}"
    data-container=".view_modal">
    <i class="fa fa-plus"></i> @lang('messages.add')
</button>
<br><br>
<table class="table table-bordered table-striped" id="lf_governorates_table" style="width:100%">
    <thead>
        <tr>
            <th>@lang('locations_fees.governorate')</th>
            <th>@lang('business.is_active')</th>
            <th>@lang('messages.action')</th>
        </tr>
    </thead>
</table>
