@php
    $row_index = $row_index ?? 0;
    $material = $material ?? null;
@endphp
<tr class="production-packaging-row">
    <td>
        {!! Form::select(
            'packaging_materials['.$row_index.'][variation_id]',
            !empty($material) ? [$material->variation_id => $material->full_name] : [],
            !empty($material) ? $material->variation_id : null,
            [
                'class' => 'form-control packaging_material_variation select2',
                'placeholder' => __('messages.please_select'),
                'style' => 'width:100%',
                'required'
            ]
        ) !!}
    </td>
    <td>
        {!! Form::text(
            'packaging_materials['.$row_index.'][quantity]',
            !empty($material) ? $material->quantity : null,
            ['class' => 'form-control input_number packaging_material_qty', 'required']
        ) !!}
        @if(!empty($material->unit_price))
            {!! Form::hidden('packaging_materials['.$row_index.'][unit_price]', $material->unit_price, ['class' => 'packaging_material_price']) !!}
        @else
            {!! Form::hidden('packaging_materials['.$row_index.'][unit_price]', 0, ['class' => 'packaging_material_price']) !!}
        @endif
    </td>
    <td class="text-right">
        <span class="display_currency packaging_material_line_total" data-currency_symbol="true">0</span>
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-xs remove-packaging-material-row">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>
