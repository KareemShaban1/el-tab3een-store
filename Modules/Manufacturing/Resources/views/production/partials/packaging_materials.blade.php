@component('components.widget', ['class' => 'box-solid', 'title' => __('manufacturing::lang.used_packaging_materials')])
    <p class="help-block">@lang('manufacturing::lang.used_packaging_materials_help')</p>
    <div class="table-responsive">
        <table class="table table-bordered" id="production_packaging_materials_table">
            <thead>
                <tr>
                    <th>@lang('manufacturing::lang.packaging_material')</th>
                    <th style="width:160px;">@lang('lang_v1.quantity')</th>
                    <th style="width:140px;">@lang('sale.total')</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($packaging_materials))
                    @foreach($packaging_materials as $index => $material)
                        @include('manufacturing::production.partials.packaging_material_row', [
                            'row_index' => $index,
                            'material' => (object) $material,
                        ])
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-success btn-sm" id="add_production_packaging_material">
        <i class="fa fa-plus"></i> @lang('manufacturing::lang.add_packaging_material')
    </button>
@endcomponent
