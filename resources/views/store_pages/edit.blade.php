<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\StorePageController::class, 'update'], [$page->id]),
            'method' => 'PUT',
            'id' => 'store_page_form',
        ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('store_pages.edit_store_page')</h4>
        </div>
        <div class="modal-body">
            @include('store_pages.partials.form_fields', ['page' => $page])
            <p class="help-block">
                <a href="{{ $page->url }}" target="_blank" rel="noopener">@lang('store_pages.view_page')</a>
            </p>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
