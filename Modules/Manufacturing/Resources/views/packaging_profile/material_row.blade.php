<div class="row material-row" data-row-index="{{ $row_index }}">
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('materials['.$row_index.'][variation_id]', __('manufacturing::lang.material') . ':*') !!}
            {!! Form::select(
                'materials['.$row_index.'][variation_id]',
                !empty($material) ? [$material->variation_id => $material->full_name] : [],
                !empty($material) ? $material->variation_id : null,
                ['class' => 'form-control material_variation select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width:100%']
            ) !!}
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            {!! Form::label('materials['.$row_index.'][material_role]', __('manufacturing::lang.material_role') . ':') !!}
            {!! Form::select('materials['.$row_index.'][material_role]', [
                'container' => __('manufacturing::lang.role_container'),
                'closure' => __('manufacturing::lang.role_closure'),
                'label' => __('manufacturing::lang.role_label'),
                'outer_carton' => __('manufacturing::lang.role_outer_carton'),
                'other' => __('manufacturing::lang.role_other'),
            ], !empty($material->material_role) ? $material->material_role : null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            {!! Form::label('materials['.$row_index.'][quantity_per_container]', __('manufacturing::lang.qty_per_container') . ':') !!}
            {!! Form::text('materials['.$row_index.'][quantity_per_container]', !empty($material->quantity_per_container) ? $material->quantity_per_container : 1, ['class' => 'form-control input_number']) !!}
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            {!! Form::label('materials['.$row_index.'][quantity_per_carton]', __('manufacturing::lang.qty_per_carton') . ':') !!}
            {!! Form::text('materials['.$row_index.'][quantity_per_carton]', !empty($material->quantity_per_carton) ? $material->quantity_per_carton : null, ['class' => 'form-control input_number']) !!}
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-danger btn-block remove-material-row"><i class="fa fa-trash"></i></button>
        </div>
    </div>
</div>
