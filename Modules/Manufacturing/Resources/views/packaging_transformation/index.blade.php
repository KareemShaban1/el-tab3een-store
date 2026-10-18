@extends('layouts.app')
@section('title', __('manufacturing::lang.transformations'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>@lang('manufacturing::lang.transformations')</h1>
</section>

<section class="content">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#tab_transform" data-toggle="tab">
                    <i class="fa fa-exchange"></i> @lang('manufacturing::lang.transform_carton_to_unit')
                </a>
            </li>
            <li>
                <a href="#tab_transformations" data-toggle="tab">
                    <i class="fa fa-list"></i> @lang('manufacturing::lang.transformations')
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_transform">
                @if(empty($profile_dropdown) || count($profile_dropdown) == 0)
                    <div class="alert alert-info" style="margin:15px;">
                        @lang('manufacturing::lang.no_transformable_profiles')
                    </div>
                @else
                    {!! Form::open(['url' => action([\Modules\Manufacturing\Http\Controllers\PackagingTransformationController::class, 'store']), 'method' => 'post', 'id' => 'packaging_transformation_form']) !!}
                    @component('components.widget', ['class' => 'box-solid'])
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                                    {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'transaction_date']) !!}
                                    </div>
                                </div>
                            </div>
                            @if(count($business_locations) == 1)
                                @php $default_location = current(array_keys($business_locations->toArray())) @endphp
                            @else
                                @php $default_location = null; @endphp
                            @endif
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                                    {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'location_id']) !!}
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('packaging_profile_id', __('manufacturing::lang.packaging_profile') . ':*') !!}
                                    {!! Form::select('packaging_profile_id', $profile_dropdown, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'packaging_profile_id']) !!}
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('carton_qty', __('manufacturing::lang.carton_qty') . ':*') !!}
                                    {!! Form::text('carton_qty', 1, ['class' => 'form-control input_number', 'required', 'id' => 'carton_qty']) !!}
                                    <p class="help-block">@lang('manufacturing::lang.carton_qty_help')</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! Form::label('notes', __('purchase.additional_notes') . ':') !!}
                                    {!! Form::text('notes', null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>
                    @endcomponent

                    <div id="transform_details_container"></div>

                    @component('components.widget', ['class' => 'box-solid'])
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary pull-right">
                                    <i class="fa fa-exchange"></i> @lang('manufacturing::lang.confirm_transformation')
                                </button>
                            </div>
                        </div>
                    @endcomponent
                    {!! Form::close() !!}
                @endif
            </div>

            <div class="tab-pane" id="tab_transformations">
                @component('components.widget', ['class' => 'box-solid'])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="packaging_transformations_table">
                            <thead>
                                <tr>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.business_location')</th>
                                    <th>@lang('manufacturing::lang.profile_name')</th>
                                    <th>@lang('manufacturing::lang.carton_product')</th>
                                    <th>@lang('manufacturing::lang.carton_qty')</th>
                                    <th>@lang('manufacturing::lang.unit_product')</th>
                                    <th>@lang('manufacturing::lang.unit_qty')</th>
                                    <th>@lang('manufacturing::lang.units_per_carton')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        if ($('#transaction_date').length) {
            $('#transaction_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });
        }

        function loadTransformDetails() {
            var profile_id = $('#packaging_profile_id').val();
            var location_id = $('#location_id').val();
            var carton_qty = $('#carton_qty').val();

            if (!profile_id || !location_id) {
                $('#transform_details_container').html('');
                return;
            }

            $.ajax({
                url: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingTransformationController::class, "getProfileDetails"]) }}',
                data: {
                    profile_id: profile_id,
                    location_id: location_id,
                    carton_qty: carton_qty
                },
                success: function(result) {
                    $('#transform_details_container').html(result);
                    __currency_convert_recursively($('#transform_details_container'));
                }
            });
        }

        $('#packaging_profile_id, #location_id, #carton_qty').on('change keyup', function() {
            loadTransformDetails();
        });

        loadTransformDetails();

        var table = null;
        $('a[href="#tab_transformations"]').on('shown.bs.tab', function() {
            if (table) {
                table.columns.adjust().draw();
                return;
            }

            table = $('#packaging_transformations_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingTransformationController::class, "index"]) }}',
                columns: [
                    { data: 'ref_no', name: 'mfg_packaging_transformations.ref_no' },
                    { data: 'transformed_at', name: 'mfg_packaging_transformations.transformed_at' },
                    { data: 'location_name', name: 'bl.name' },
                    { data: 'profile_name', name: 'mpp.name' },
                    { data: 'carton_name', name: 'carton_name' },
                    { data: 'carton_qty', name: 'mfg_packaging_transformations.carton_qty' },
                    { data: 'unit_name', name: 'unit_name' },
                    { data: 'unit_qty', name: 'mfg_packaging_transformations.unit_qty' },
                    { data: 'units_per_carton', name: 'mfg_packaging_transformations.units_per_carton' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                fnDrawCallback: function() {
                    __currency_convert_recursively($('#packaging_transformations_table'));
                }
            });
        });
    });
</script>
@endsection
