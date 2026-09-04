@component('components.widget', ['class' => 'box-solid'])
<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('name', __('manufacturing::lang.profile_name') . ':*') !!}
            {!! Form::text('name', !empty($profile) ? $profile->name : null, ['class' => 'form-control', 'required']) !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('bulk_variation_id', __('manufacturing::lang.bulk_product') . ':*') !!}
            {!! Form::select(
                'bulk_variation_id',
                !empty($profile) && !empty($profile->bulkVariation) ? [$profile->bulk_variation_id => \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($profile->bulkVariation)] : [],
                !empty($profile) ? $profile->bulk_variation_id : null,
                ['class' => 'form-control product_variation select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width:100%']
            ) !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('output_variation_id', __('manufacturing::lang.output_product') . ':*') !!}
            {!! Form::select(
                'output_variation_id',
                !empty($profile) && !empty($profile->outputVariation) ? [$profile->output_variation_id => \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($profile->outputVariation)] : [],
                !empty($profile) ? $profile->output_variation_id : null,
                ['class' => 'form-control product_variation select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width:100%']
            ) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('container_type', __('manufacturing::lang.container_type') . ':*') !!}
            {!! Form::select('container_type', ['bottle' => __('manufacturing::lang.bottle'), 'bag' => __('manufacturing::lang.bag')], !empty($profile) ? $profile->container_type : 'bottle', ['class' => 'form-control select2', 'required']) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('container_volume', __('manufacturing::lang.container_volume') . ':') !!}
            {!! Form::text('container_volume', !empty($profile) ? $profile->container_volume : null, ['class' => 'form-control input_number']) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('units_per_carton', __('manufacturing::lang.units_per_carton') . ':*') !!}
            {!! Form::number('units_per_carton', !empty($profile) ? $profile->units_per_carton : 12, ['class' => 'form-control', 'min' => 1, 'required']) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('bulk_qty_per_container', __('manufacturing::lang.bulk_qty_per_container') . ':*') !!}
            {!! Form::text('bulk_qty_per_container', !empty($profile) ? $profile->bulk_qty_per_container : null, ['class' => 'form-control input_number', 'required']) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('waste_percent', __('manufacturing::lang.waste_percent') . ':') !!}
            {!! Form::text('waste_percent', !empty($profile) ? $profile->waste_percent : 0, ['class' => 'form-control input_number']) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <br>
            <label>
                {!! Form::checkbox('is_active', 1, empty($profile) || !empty($profile->is_active), ['class' => 'input-icheck']) !!}
                @lang('manufacturing::lang.active')
            </label>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            {!! Form::label('instructions', __('manufacturing::lang.instructions') . ':') !!}
            {!! Form::textarea('instructions', !empty($profile) ? $profile->instructions : null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
    </div>
</div>
@endcomponent

@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.packaging_materials')])
<div id="materials_container">
    @if(!empty($profile) && $profile->materials->count())
        @foreach($profile->materials as $index => $material)
            @include('manufacturing::packaging_profile.material_row', [
                'row_index' => $index,
                'material' => (object) [
                    'variation_id' => $material->variation_id,
                    'full_name' => $material->variation->full_name ?? '',
                    'material_role' => $material->material_role,
                    'quantity_per_container' => $material->quantity_per_container,
                    'quantity_per_carton' => $material->quantity_per_carton,
                ]
            ])
        @endforeach
    @endif
</div>
<button type="button" class="btn btn-success" id="add_material_row"><i class="fa fa-plus"></i> @lang('manufacturing::lang.add_material')</button>
@endcomponent
