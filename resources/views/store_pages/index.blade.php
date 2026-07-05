@extends('layouts.app')

@section('title', __('store_pages.store_pages'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fa fas fa-file-alt"></i> @lang('store_pages.store_pages')
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('store_pages.store_pages')])
        <button type="button" class="btn btn-sm btn-primary btn-modal pull-right"
            data-href="{{ action([\App\Http\Controllers\StorePageController::class, 'create']) }}"
            data-container=".view_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')
        </button>
        <br><br>
        <table class="table table-bordered table-striped" id="store_pages_table" style="width:100%">
            <thead>
                <tr>
                    <th>@lang('store_pages.title')</th>
                    <th>@lang('store_pages.slug')</th>
                    <th>@lang('store_pages.page_type')</th>
                    <th>@lang('store_pages.footer_group')</th>
                    <th>@lang('store_pages.placement')</th>
                    <th>@lang('lang_v1.sort_order')</th>
                    <th>@lang('business.is_active')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var store_pages_table = $('#store_pages_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\StorePageController::class, 'index']) }}',
            columns: [
                { data: 'title', name: 'store_pages.title' },
                { data: 'slug', name: 'store_pages.slug' },
                { data: 'page_type', name: 'store_pages.page_type' },
                { data: 'footer_group', name: 'store_pages.footer_group' },
                { data: 'placement', name: 'placement', orderable: false, searchable: false },
                { data: 'sort_order', name: 'store_pages.sort_order' },
                { data: 'is_active', name: 'store_pages.is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[5, 'asc']]
        });

        function initStorePageEditor() {
            if (typeof tinymce === 'undefined' || !$('#store_page_content').length) {
                return;
            }

            if (tinymce.get('store_page_content')) {
                tinymce.get('store_page_content').remove();
            }

            tinymce.init({
                selector: '#store_page_content',
                height: 320,
                plugins: 'link lists code table',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link table | code',
                menubar: false,
            });
        }

        $(document).on('shown.bs.modal', '.view_modal', function() {
            initStorePageEditor();
        });

        $(document).on('hidden.bs.modal', '.view_modal', function() {
            if (typeof tinymce !== 'undefined' && tinymce.get('store_page_content')) {
                tinymce.get('store_page_content').remove();
            }
        });

        $(document).on('submit', '#store_page_form', function(e) {
            e.preventDefault();
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            $.ajax({
                method: $(this).attr('method') || 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        store_pages_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('click', '.delete_store_page_button', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                store_pages_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
