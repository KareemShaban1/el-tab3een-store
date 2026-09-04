@extends('layouts.app')
@section('title', __('manufacturing::lang.packaging_profiles'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>@lang('manufacturing::lang.packaging_profiles')
        <small>@lang('manufacturing::lang.packaging_profiles_help')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            @if(\Modules\Manufacturing\Support\PackagingFeature::userCanManageProfiles())
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProfileController::class, 'create']) }}">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </a>
                </div>
            @endif
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="packaging_profiles_table">
                <thead>
                    <tr>
                        <th>@lang('manufacturing::lang.profile_name')</th>
                        <th>@lang('manufacturing::lang.bulk_product')</th>
                        <th>@lang('manufacturing::lang.output_product')</th>
                        <th>@lang('manufacturing::lang.container_type')</th>
                        <th>@lang('manufacturing::lang.units_per_carton')</th>
                        <th>@lang('manufacturing::lang.status')</th>
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
        var table = $('#packaging_profiles_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProfileController::class, "index"]) }}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'bulk_product', name: 'bulk_product', orderable: false, searchable: false },
                { data: 'output_product', name: 'output_product', orderable: false, searchable: false },
                { data: 'container_type', name: 'container_type' },
                { data: 'units_per_carton', name: 'units_per_carton' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', '.delete-packaging-profile', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    var href = $(e.currentTarget).data('href');
                    $.ajax({
                        method: 'DELETE',
                        url: href,
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
