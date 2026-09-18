<script type="text/javascript">
    $(document).ready(function() {
        var material_row_index = $('#materials_container .material-row').length;

        function initProductSelect($el) {
            $el.select2({
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { term: params.term };
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
                    if (!data.id) return data.text;
                    var html = data.text + ' — ' + data.name;
                    if (data.type == 'variable') {
                        html += ' (' + data.variation + ')';
                    }
                    html += ' (' + data.sub_sku + ')';
                    return html;
                },
                templateSelection: function(data) {
                    return data.text || data.name;
                }
            });
        }

        $('.product_variation, .material_variation').each(function() {
            initProductSelect($(this));
        });

        $('#add_material_row').on('click', function() {
            $.ajax({
                url: '{{ action([\Modules\Manufacturing\Http\Controllers\PackagingProfileController::class, "getMaterialRow"]) }}',
                data: { row_index: material_row_index },
                success: function(result) {
                    $('#materials_container').append(result);
                    var $newRow = $('#materials_container .material-row:last');
                    initProductSelect($newRow.find('.material_variation'));
                    $newRow.find('.material_role').select2();
                    toggleCartonProfileFields();
                    material_row_index++;
                }
            });
        });

        $(document).on('click', '.remove-material-row', function() {
            $(this).closest('.material-row').remove();
        });

        // Bottle/cap/label → qty per container only; outer carton → qty per carton only
        function usesCarton() {
            return $('#uses_carton').is(':checked');
        }

        function toggleCartonProfileFields() {
            var enabled = usesCarton();
            $('.units-per-carton-wrap').toggle(enabled);
            if (!enabled) {
                $('#units_per_carton').val('');
            } else if (!$('#units_per_carton').val()) {
                $('#units_per_carton').val(12);
            }

            $('#materials_container .material-row').each(function() {
                var $row = $(this);
                var $role = $row.find('.material_role');
                var $outerOpt = $role.find('option[value="outer_carton"]');
                if (enabled) {
                    $outerOpt.prop('disabled', false);
                } else {
                    if ($role.val() === 'outer_carton') {
                        $role.val('container').trigger('change');
                    }
                    $outerOpt.prop('disabled', true);
                }
                $role.trigger('change.select2');
                toggleMaterialQtyFields($row);
            });
        }

        function toggleMaterialQtyFields($row) {
            var role = $row.find('.material_role').val();
            var $perContainer = $row.find('.qty-per-container-wrap');
            var $perCarton = $row.find('.qty-per-carton-wrap');
            var cartonMode = usesCarton();

            if (role === 'outer_carton' && cartonMode) {
                $perContainer.hide();
                $perCarton.show();
                if (!$row.find('.quantity_per_carton').val()) {
                    $row.find('.quantity_per_carton').val(1);
                }
            } else if (['container', 'closure', 'label'].indexOf(role) !== -1 || !cartonMode) {
                $perContainer.show();
                $perCarton.hide();
                $row.find('.quantity_per_carton').val('');
                if (!$row.find('.quantity_per_container').val()) {
                    $row.find('.quantity_per_container').val(1);
                }
            } else {
                $perContainer.show();
                $perCarton.toggle(cartonMode);
            }
        }

        toggleCartonProfileFields();

        $('#uses_carton').on('ifChanged change', function() {
            toggleCartonProfileFields();
        });

        $('#materials_container .material-row').each(function() {
            toggleMaterialQtyFields($(this));
        });

        $(document).on('change', '.material_role', function() {
            toggleMaterialQtyFields($(this).closest('.material-row'));
        });
    });
</script>
