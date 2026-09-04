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
                    initProductSelect($('#materials_container .material-row:last .material_variation'));
                    material_row_index++;
                }
            });
        });

        $(document).on('click', '.remove-material-row', function() {
            $(this).closest('.material-row').remove();
        });
    });
</script>
