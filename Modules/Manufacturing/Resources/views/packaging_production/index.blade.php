@extends('layouts.app')
@section('title', __('manufacturing::lang.packaging_production'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>@lang('manufacturing::lang.packaging_production')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, 'create']) }}">
                    <i class="fa fa-plus"></i> @lang('manufacturing::lang.add_packaging_production')
                </a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="packaging_production_table">
                <thead>
                    <tr>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.business_location')</th>
                        <th>@lang('manufacturing::lang.profile_name')</th>
                        <th>@lang('sale.product')</th>
                        <th>@lang('manufacturing::lang.containers')</th>
                        <th>@lang('manufacturing::lang.cartons')</th>
                        <th>@lang('sale.total')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var table = $('#packaging_production_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, "index"]) }}',
            columns: [
                { data: 'ref_no', name: 'ref_no' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'profile_name', name: 'mpp.name' },
                { data: 'product_name', name: 'product_name' },
                { data: 'mfg_containers_count', name: 'mfg_containers_count' },
                { data: 'mfg_cartons_count', name: 'mfg_cartons_count' },
                { data: 'final_total', name: 'final_total' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            fnDrawCallback: function() {
                __currency_convert_recursively($('#packaging_production_table'));
            }
        });

        $(document).on('click', '.delete-packaging-production', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: $(e.currentTarget).data('href'),
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                table.ajax.reload();
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
