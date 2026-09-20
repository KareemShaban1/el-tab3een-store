<script type="text/javascript">
	$(document).ready( function() {
        jQuery.validator.addMethod("notEmpty", function(value, element, param) {
            return __number_uf(value) > 0
        }, "{{__('manufacturing::lang.quantity_greater_than_zero')}}");

        jQuery.validator.addMethod("notEqualToWastedQuantity", function(value, element, param) {
            var waste_qty = __read_number($('#mfg_wasted_units'));
            var qty = __number_uf(value);
            return qty > waste_qty;
        }, "{{__('manufacturing::lang.waste_qty_less_than_qty')}}");

		$('#transaction_date').datetimepicker({
	        format: moment_date_format + ' ' + moment_time_format,
	        ignoreReadonly: true,
	    });

	    $('#exp_date').datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });

	    production_form_validator = $('#production_form').validate();
	});
	$(document).on('change', '#production_form #variation_id, #production_form #location_id', function () {
		var variation_id = $("#variation_id").val();
		var location_id = $("#location_id").val();
		
		if(variation_id && location_id) {
			$.ajax({
	            url: "/manufacturing/get-recipe-details?variation_id=" + variation_id + "&location_id=" + location_id,
	            dataType: 'json',
	            success: function(result) {
	                $('#enter_ingredients_table').html(result.ingredient_table);
	                if (result.is_sub_unit) {
	                	$('#recipe_quantity_input').removeClass('input-group');
	                	$('#recipe_quantity_input').addClass('input_inline');
	                	$('#unit_html').removeClass('input-group-addon');
	                } else {
	                	$('#recipe_quantity_input').addClass('input-group');
	                	$('#recipe_quantity_input').removeClass('input_inline');
	                	$('#unit_html').addClass('input-group-addon');
	                }
	                __write_number($('#recipe_quantity'), result.recipe.total_quantity);
	                $('#unit_html').html(result.unit_html);
	                $('#final_quantity_unit_html').html(result.is_sub_unit ? '' : result.unit_name);
	                if (!result.is_sub_unit) {
	                	$('#final_quantity_unit_html').text(result.unit_name);
	                }
	                $('#wasted_units_text').text(result.unit_name);

                    var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), result.recipe.total_quantity);
                    __write_number($('#mfg_wasted_units'), mfg_wasted_units);
                    __write_number($('#production_cost'), result.recipe.extra_cost);
                    $('#mfg_production_cost_type').val(result.recipe.production_cost_type);

	                __currency_convert_recursively($('#enter_ingredients_table'));
                    updateFinalQuantity();
                    calculateRecipeTotal();
	            },
	        });
		} else {
			$('#enter_ingredients_table').html('');
			updateFinalQuantity();
	        calculateRecipeTotal();
		}
	});

	$(document).on('change', '#production_cost, select.sub_unit, input.mfg_waste_percent, #mfg_production_cost_type', function(){
		calculateRecipeTotal();
	});

	$(document).on('change keyup', '#recipe_quantity, #mfg_wasted_units', function(){
		updateFinalQuantity();
	});

    $(document).on('change', '#recipe_quantity, #sub_unit_id', function(){
        scaleIngredientQuantities();
    });

	$(document).on('change', '#sub_unit_id', function(){
		var unit_name = $(this)
                .find(':selected')
                .data('unit_name');
            $('#wasted_units_text').text(unit_name);
            $('#final_quantity_unit_html').text(unit_name);
            updateFinalQuantity();
	});

	$(document).on('change', '.total_quantities', function(){
		if (production_form_validator) {
    		production_form_validator.element($(this));
		}
		calculateRecipeTotal();
	});

    function __mfg_qty_precision() {
        var precision = (typeof __quantity_precision !== 'undefined') ? parseInt(__quantity_precision, 10) : 4;
        if (isNaN(precision) || precision < 4) {
            precision = 4;
        }
        return precision;
    }

    function __mfg_recipe_unit_multiplier() {
        var multiplier = 1;
        if ($('#sub_unit_id').length) {
            var $opt = $('#sub_unit_id').find(':selected');
            var raw = $opt.attr('data-multiplier');
            if (raw === undefined || raw === null || raw === '') {
                raw = $opt.data('multiplier');
            }
            var selected_multiplier = parseFloat(raw);
            if (!isNaN(selected_multiplier) && selected_multiplier > 0) {
                multiplier = selected_multiplier;
            }
        }
        return multiplier;
    }

    /**
     * Final quantity added to finished product stock =
     * ingredients table footer "Final quantity" total − wasted units
     */
    function updateFinalQuantity() {
        var table_total_qty = 0;

        if ($('#footer_total_final_quantity').length) {
            table_total_qty = __number_uf($('#footer_total_final_quantity').text());
        }
        if (isNaN(table_total_qty) || table_total_qty < 0) {
            table_total_qty = 0;
        }

        var waste_qty = __read_number($('#mfg_wasted_units'));
        if (isNaN(waste_qty) || waste_qty < 0) {
            waste_qty = 0;
        }

        var final_qty = table_total_qty - waste_qty;
        if (final_qty < 0 || isNaN(final_qty) || !isFinite(final_qty)) {
            final_qty = 0;
        }

        __write_number($('#final_quantity'), final_qty, false, __mfg_qty_precision());
    }

    /**
     * Scale ingredient rows from the quantities loaded with the recipe,
     * instead of relying on tiny unit_quantity values that round to 0.
     */
    function scaleIngredientQuantities() {
        var $table = $('#ingredients_for_unit_recipe_table');
        if ($table.length === 0) {
            updateFinalQuantity();
            calculateRecipeTotal();
            return;
        }

        var recipe_quantity = __read_number($('#recipe_quantity'));
        if (isNaN(recipe_quantity) || recipe_quantity < 0) {
            recipe_quantity = 0;
        }

        var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), recipe_quantity);
        __write_number($('#mfg_wasted_units'), mfg_wasted_units);
        updateFinalQuantity();

        var current_multiplier = __mfg_recipe_unit_multiplier();
        var base_recipe_qty = parseFloat($table.attr('data-base-recipe-quantity'));
        var base_recipe_multiplier = parseFloat($table.attr('data-base-recipe-multiplier'));

        if (isNaN(base_recipe_qty) || base_recipe_qty <= 0) {
            base_recipe_qty = 1;
        }
        if (isNaN(base_recipe_multiplier) || base_recipe_multiplier <= 0) {
            base_recipe_multiplier = 1;
        }

        var scale = (recipe_quantity * current_multiplier) / (base_recipe_qty * base_recipe_multiplier);
        if (isNaN(scale) || !isFinite(scale)) {
            scale = 0;
        }

        var qty_precision = __mfg_qty_precision();

        $table.find('tbody tr').each(function() {
            var $qtyInput = $(this).find('.total_quantities');
            if ($qtyInput.length === 0) {
                return;
            }

            var base_quantity = parseFloat($qtyInput.attr('data-base-quantity'));
            if (isNaN(base_quantity)) {
                // Fallback to classic unit_quantity formula
                var line_unit_quantity = parseFloat($(this).find('.unit_quantity').attr('data-unit_quantity'));
                if (isNaN(line_unit_quantity)) {
                    line_unit_quantity = parseFloat($(this).find('.unit_quantity').val());
                }
                if (isNaN(line_unit_quantity)) {
                    line_unit_quantity = 0;
                }
                var line_multiplier = __mfg_getLineMultiplier($(this));
                if (isNaN(line_multiplier) || line_multiplier <= 0) {
                    line_multiplier = 1;
                }
                base_quantity = (base_recipe_qty * base_recipe_multiplier * line_unit_quantity) / line_multiplier;
            }

            var line_total_quantity = base_quantity * scale;
            if (isNaN(line_total_quantity) || !isFinite(line_total_quantity)) {
                line_total_quantity = 0;
            }

            __write_number($qtyInput, line_total_quantity, false, qty_precision);
        });

        calculateRecipeTotal();
    }

    function __mfg_getLineMultiplier($row) {
        var $select = $row.find('select.sub_unit');
        if (!$select.length) {
            return 1;
        }
        var $opt = $select.find(':selected');
        // Prefer attr — jQuery .data() can mis-handle small decimals like 0.001
        var raw = $opt.attr('data-multiplier');
        if (raw === undefined || raw === null || raw === '') {
            raw = $opt.data('multiplier');
        }
        var multiplier = parseFloat(raw);
        if (isNaN(multiplier) || multiplier <= 0) {
            return 1;
        }
        return multiplier;
    }

	function calculateRecipeTotal() {
		var recipe_quantity = __read_number($('#recipe_quantity'));
        var multiplier = __mfg_recipe_unit_multiplier();
        recipe_quantity = recipe_quantity * multiplier;

        var total_ingredients_cost = 0;
        var total_input_quantity = 0;
        var total_final_quantity = 0;
        var qty_precision = __mfg_qty_precision();

        $('#ingredients_for_unit_recipe_table tbody tr').each( function() {
            if ($(this).find('.ingredient_price').length > 0) {
                var line_unit_price = parseFloat($(this).find('.ingredient_price').val()) || 0;
                var line_total_quantity = __read_number($(this).find('.total_quantities'));
                var line_multiplier = __mfg_getLineMultiplier($(this));
                var line_waste_percent = __read_number($(this).find('.mfg_waste_percent'));

                var line_final_quantity = __substract_percent(line_total_quantity, line_waste_percent);

                // dpp is per base unit (e.g. kg); qty may be in sub-unit (e.g. gram = 0.001 kg)
                var line_total = line_unit_price * line_total_quantity * line_multiplier;
                $(this).find('span.ingredient_total_price').text(__currency_trans_from_en(line_total, true));
                $(this).find('span.row_final_quantity').text(
                    __number_f(line_final_quantity, false, false, qty_precision)
                );

                var line_unit_name = '';

                if ($(this).find('.sub_unit').length) {
                    line_unit_name = $(this).find('.sub_unit')
                    .find('option:selected')
                    .text();
                } else {
                    line_unit_name = $(this).find('.line_unit_span').text();
                }
                $(this).find('.row_unit_text').text(line_unit_name);

                total_ingredients_cost += line_total;
                // Sum in base units so g/kg are not mixed as equal
                total_input_quantity += line_total_quantity * line_multiplier;
                total_final_quantity += line_final_quantity * line_multiplier;
            }
        });

        $('#footer_total_input_quantity').text(__number_f(total_input_quantity, false, false, qty_precision));
        $('#footer_total_final_quantity').text(__number_f(total_final_quantity, false, false, qty_precision));
        $('#total_ingredient_price').text(__currency_trans_from_en(total_ingredients_cost, true));

        // Keep Final Quantity in sync with footer final total − waste
        updateFinalQuantity();

        var production_cost = __read_number($('#production_cost'));
        var production_cost_type = $('#mfg_production_cost_type').val();
        if (production_cost_type == 'percentage') {
            production_cost = __calculate_amount('percentage', production_cost, total_ingredients_cost);
        } else if(production_cost_type == 'per_unit') {
            production_cost = production_cost * __read_number($('#recipe_quantity'));
        }
        $('span#total_production_cost').text(__currency_trans_from_en(production_cost, true));
        var final_price = total_ingredients_cost + production_cost;
        __write_number($('#final_total'), final_price);
        $('span#final_total_text').text(__currency_trans_from_en(final_price, true));
        __write_number($('#total'), total_ingredients_cost);
    }

    $(document).on('focus', 'select.sub_unit', function() {
        $(this).data('prev-multiplier', __mfg_getLineMultiplier($(this).closest('tr')));
    });

    $(document).on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var selected_option = $(this).find(':selected');
        var multiplier = __mfg_getLineMultiplier(tr);
        var allow_decimal = parseInt(selected_option.attr('data-allow_decimal'), 10);
        if (isNaN(allow_decimal)) {
            allow_decimal = parseInt(selected_option.data('allow_decimal'), 10);
        }

        var qty_element = tr.find('input.total_quantities');
        var prev_multiplier = parseFloat($(this).data('prev-multiplier'));
        if (isNaN(prev_multiplier) || prev_multiplier <= 0) {
            prev_multiplier = 1;
        }
        // Keep same base qty when switching e.g. kg ↔ gram
        var current_qty = __read_number(qty_element);
        if (!isNaN(current_qty) && multiplier > 0) {
            var base_qty = current_qty * prev_multiplier;
            var new_qty = base_qty / multiplier;
            __write_number(qty_element, new_qty, false, __mfg_qty_precision());
        }
        $(this).data('prev-multiplier', multiplier);

        var base_max_avlbl = qty_element.attr('data-qty_available');
        if (base_max_avlbl === undefined) {
            base_max_avlbl = qty_element.data('qty_available');
        }
        var error_msg_line = 'pos_max_qty_error';

        qty_element.attr('data-decimal', allow_decimal ? 1 : 0);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        if (qty_element.rules) {
            qty_element.rules('add', {
                abs_digit: abs_digit,
            });
        }

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.attr('data-unit_name') || selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            if (qty_element.rules) {
                qty_element.rules('add', {
                    'max-value': max_avlbl,
                    messages: {
                        'max-value': max_err_msg,
                    },
                });
            }
        }
        calculateRecipeTotal();
    });
</script>