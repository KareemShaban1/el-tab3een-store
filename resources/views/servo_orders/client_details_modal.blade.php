<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ __('contact.contact_info', ['contact' => __('role.customer')]) }}</h4>
        </div>
        <div class="modal-body">
            @include('contact.contact_basic_info')

            @if (! empty($contact->email))
                <strong><i class="fa fa-envelope margin-r-5"></i> @lang('business.email')</strong>
                <p class="text-muted">{{ $contact->email }}</p>
            @endif
        </div>
        <div class="modal-footer">
            @can('customer.view')
                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'show'], [$contact->id]) }}"
                    class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary" target="_blank">
                    @lang('messages.view') @lang('contact.contact')
                </a>
            @endcan
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white tw-dw-btn-sm" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
</div>
