<script type="text/javascript">
    $(document).ready(function() {
        function refreshPackagingMaterialTotals() {
            $('#production_packaging_materials_table tbody tr.production-packaging-row').each(function() {
                var qty = __read_number($(this).find('.packaging_material_qty'));
                var price = parseFloat($(this).find('.packaging_material_price').val()) || 0;
                if (isNaN(qty) || qty < 0) {
                    qty = 0;
                }
                $(this).find('.packaging_material_line_total').text(__currency_trans_from_en(qty * price, true));
            });
        }

        function initPackagingMaterialSelect($el) {
            $el.select2({
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            only_packaging_materials: 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                var text = item.name;
                                if (item.type == 'variable' && item.variation) {
                                    text += ' - ' + item.variation;
                                }
                                text += ' (' + item.sub_sku + ')';
                                return { id: item.variation_id, text: text };
                            })
                        };
                    },
                },
                minimumInputLength: 1,
                escapeMarkup: function(m) { return m; },
                templateResult: function(data) {
                    return data.text;
                },
                templateSelection: function(data) {
                    return data.text || data.name;
                }
            });
        }

        var packaging_material_row_index = $('#production_packaging_materials_table tbody tr.production-packaging-row').length;

        $('#add_production_packaging_material').on('click', function() {
            $.ajax({
                url: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, "getPackagingMaterialRow"]) }}',
                data: { row_index: packaging_material_row_index },
                success: function(result) {
                    $('#production_packaging_materials_table tbody').append(result);
                    var $row = $('#production_packaging_materials_table tbody tr.production-packaging-row:last');
                    initPackagingMaterialSelect($row.find('.packaging_material_variation'));
                    packaging_material_row_index++;
                }
            });
        });

        $(document).on('click', '.remove-packaging-material-row', function() {
            $(this).closest('tr').remove();
            refreshPackagingMaterialTotals();
        });

        $(document).on('change', '.packaging_material_variation', function() {
            var $row = $(this).closest('tr');
            var variation_id = $(this).val();
            if (!variation_id) {
                $row.find('.packaging_material_price').val(0);
                refreshPackagingMaterialTotals();
                return;
            }

            $.ajax({
                url: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProductionController::class, "getPackagingMaterialRow"]) }}',
                data: { variation_id: variation_id, json: 1 },
                success: function(result) {
                    $row.find('.packaging_material_price').val(result.unit_price || 0);
                    refreshPackagingMaterialTotals();
                }
            });
        });

        $(document).on('change keyup', '.packaging_material_qty', function() {
            refreshPackagingMaterialTotals();
        });

        $('#production_packaging_materials_table .packaging_material_variation').each(function() {
            initPackagingMaterialSelect($(this));
        });

        refreshPackagingMaterialTotals();
    });
</script>
