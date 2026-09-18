@extends('layouts.app')
@section('title', __('manufacturing::lang.edit_packaging_production'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>@lang('manufacturing::lang.edit_packaging_production')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, 'update'], [$production->id]), 'method' => 'put', 'id' => 'packaging_production_form']) !!}
    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                    {!! Form::text('ref_no', $production->ref_no, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('transaction_date', __('manufacturing::lang.mfg_date') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        {!! Form::text('transaction_date', @format_datetime($production->transaction_date), ['class' => 'form-control', 'readonly', 'required']) !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                    {!! Form::select('location_id', $business_locations, $production->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'location_id']) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('packaging_profile_id', __('manufacturing::lang.packaging_profile') . ':*') !!}
                    {!! Form::select('packaging_profile_id', $profile_dropdown, $production->mfg_packaging_profile_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'packaging_profile_id']) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('containers_count', __('manufacturing::lang.containers_count') . ':*') !!}
                    {!! Form::text('containers_count', $production->mfg_containers_count, ['class' => 'form-control input_number', 'required', 'id' => 'containers_count']) !!}
                    <p class="help-block">@lang('manufacturing::lang.containers_count_help')</p>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('final_total', __('sale.total') . ':*') !!}
                    {!! Form::text('final_total', @num_format($production->final_total), ['class' => 'form-control input_number', 'required', 'id' => 'final_total']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    <div id="profile_details_container"></div>

    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('finalize', 1, false, ['class' => 'input-icheck', 'id' => 'finalize']) !!}
                        @lang('manufacturing::lang.finalize') @show_tooltip(__('manufacturing::lang.packaging_finalize_tooltip'))
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
            </div>
        </div>
    @endcomponent
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        function loadProfileDetails() {
            var profile_id = $('#packaging_profile_id').val();
            var location_id = $('#location_id').val();
            var containers_count = $('#containers_count').val();

            if (!profile_id || !location_id) {
                $('#profile_details_container').html('');
                return;
            }

            $.ajax({
                url: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, "getProfileDetails"]) }}',
                data: {
                    profile_id: profile_id,
                    location_id: location_id,
                    containers_count: containers_count
                },
                success: function(result) {
                    $('#profile_details_container').html(result);
                    __currency_convert_recursively($('#profile_details_container'));
                }
            });
        }

        $('#packaging_profile_id, #location_id, #containers_count').on('change keyup', function() {
            loadProfileDetails();
        });

        loadProfileDetails();
    });
</script>
@endsection
