@extends('layouts.app')

@section('title', __('lang_v1.hero_banners'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fa fas fa-images"></i> @lang('lang_v1.hero_banners')
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.hero_banners')])
        <button type="button" class="btn btn-sm btn-primary btn-modal pull-right"
            data-href="{{ action([\App\Http\Controllers\StoreHeroBannerController::class, 'create']) }}"
            data-container=".view_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')
        </button>
        <br><br>
        <table class="table table-bordered table-striped" id="hero_banners_table" style="width:100%">
            <thead>
                <tr>
                    <th>@lang('lang_v1.image')</th>
                    <th>@lang('lang_v1.hero_badge')</th>
                    <th>@lang('lang_v1.hero_title')</th>
                    <th>@lang('lang_v1.hero_link')</th>
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
        var hero_banners_table = $('#hero_banners_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\StoreHeroBannerController::class, 'index']) }}',
            columns: [
                { data: 'image', name: 'store_hero_banners.image', orderable: false, searchable: false },
                { data: 'badge', name: 'store_hero_banners.badge' },
                { data: 'title', name: 'store_hero_banners.title' },
                { data: 'link', name: 'link', orderable: false, searchable: false },
                { data: 'sort_order', name: 'store_hero_banners.sort_order' },
                { data: 'is_active', name: 'store_hero_banners.is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            columnDefs: [{
                targets: -1,
                orderable: false,
                searchable: false
            }],
            order: [[4, 'asc']]
        });

        function submitHeroBannerForm(form) {
            var formData = new FormData(form);
            $.ajax({
                method: $(form).attr('method') || 'POST',
                url: $(form).attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        hero_banners_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        }

        $(document).on('submit', '#hero_banner_form', function(e) {
            e.preventDefault();
            submitHeroBannerForm(this);
        });

        $(document).on('click', '.delete_hero_banner_button', function(e) {
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
                                hero_banners_table.ajax.reload();
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
