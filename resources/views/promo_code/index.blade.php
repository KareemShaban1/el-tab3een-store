@extends('layouts.app')
@section('title', __('lang_v1.promo_codes'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.promo_codes')</h1>
</section>

<section class="content">
    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200">
        <div class="tw-p-4 sm:tw-p-5">
            <div class="tw-flex tw-gap-2.5 tw-justify-end">
                @can('discount.access')
                    <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                        data-href="{{ action([\App\Http\Controllers\PromoCodeController::class, 'create']) }}"
                        data-container=".promo_code_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </a>
                @endcan
            </div>
            <div class="tw-mt-5">
                <table class="table table-bordered table-striped" id="promo_codes_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.promo_code')</th>
                            <th>@lang('sale.discount')</th>
                            <th>@lang('sale.discount_amount')</th>
                            <th>@lang('lang_v1.promo_application')</th>
                            <th>@lang('lang_v1.used_count')</th>
                            <th>@lang('lang_v1.max_uses_total')</th>
                            <th>@lang('lang_v1.is_active')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade promo_code_modal" tabindex="-1" role="dialog"></div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var promo_codes_table = $('#promo_codes_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\PromoCodeController::class, "index"]) }}',
            columns: [
                { data: 'code', name: 'promo_codes.code' },
                { data: 'discount_name', name: 'd.name' },
                { data: 'discount_amount', name: 'd.discount_amount' },
                { data: 'promo_application', name: 'd.promo_application' },
                { data: 'used_count', name: 'promo_codes.used_count' },
                { data: 'max_uses_total', name: 'promo_codes.max_uses_total' },
                { data: 'is_active', name: 'promo_codes.is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#promo_code_form', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                dataType: 'json',
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        $('.promo_code_modal').modal('hide');
                        toastr.success(result.msg);
                        promo_codes_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', '.delete_promo_code_button', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                promo_codes_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });

        $(document).on('shown.bs.modal', '.promo_code_modal', function() {
            $('.discount_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });
        });
    });
</script>
@endsection
