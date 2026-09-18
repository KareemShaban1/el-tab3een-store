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
	                $('#wasted_units_text').text(result.unit_name);

                    var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), result.recipe.total_quantity);
                    __write_number($('#mfg_wasted_units'), mfg_wasted_units);
                    __write_number($('#production_cost'), result.recipe.extra_cost);
                    $('#mfg_production_cost_type').val(result.recipe.production_cost_type);

	                __currency_convert_recursively($('#enter_ingredients_table'));
                    calculateRecipeTotal();
	            },
	        });
		} else {
			$('#enter_ingredients_table').html('');
	        calculateRecipeTotal();
		}
	});

	$(document).on('change', '#production_cost, select.sub_unit, input.mfg_waste_percent, #mfg_production_cost_type', function(){
		calculateRecipeTotal();
	});

    $(document).on('change', '#recipe_quantity, #sub_unit_id', function(){
        var recipe_quantity = __read_number($('#recipe_quantity'));

        var mfg_wasted_units = __calculate_amount('percentage', $('#waste_percent').val(), recipe_quantity);
        __write_number($('#mfg_wasted_units'), mfg_wasted_units);

        var multiplier = 1;
        if ($('#sub_unit_id').length) {
            var selected_multiplier = parseFloat(
                $('#sub_unit_id').find(':selected').data('multiplier')
            );
            if (!isNaN(selected_multiplier) && selected_multiplier > 0) {
                multiplier = selected_multiplier;
            }
            recipe_quantity = recipe_quantity * multiplier;
        }
        $('#ingredients_for_unit_recipe_table tbody tr').each( function() {
            var $unitQtyInput = $(this).find('.unit_quantity');
            if ($unitQtyInput.length === 0) {
                return;
            }

            var line_unit_quantity = parseFloat($unitQtyInput.data('unit_quantity'));
            if (isNaN(line_unit_quantity)) {
                line_unit_quantity = parseFloat($unitQtyInput.val());
            }
            if (isNaN(line_unit_quantity)) {
                line_unit_quantity = 0;
            }

            var line_multiplier = __getUnitMultiplier($(this));
            if (!line_multiplier || isNaN(line_multiplier) || line_multiplier === 0) {
                line_multiplier = 1;
            }

            var line_total_quantity = (recipe_quantity * line_unit_quantity) / line_multiplier;
            if (isNaN(line_total_quantity) || !isFinite(line_total_quantity)) {
                line_total_quantity = 0;
            }

            // Use quantity precision (not currency) so small ingredient qtys don't round to 0.00
            __write_number(
                $(this).find('.total_quantities'),
                line_total_quantity,
                false,
                (typeof __quantity_precision !== 'undefined' ? __quantity_precision : 4)
            );
            $(this).find('.total_quantities').trigger('change');
        });
    });

	$(document).on('change', '#sub_unit_id', function(){
		var unit_name = $(this)
                .find(':selected')
                .data('unit_name');
            $('#wasted_units_text').text(unit_name);
	});

	$(document).on('change', '.total_quantities', function(){
		if (production_form_validator) {
    		production_form_validator.element($(this));
		}
		calculateRecipeTotal();
	});

	function calculateRecipeTotal() {
		var recipe_quantity = __read_number($('#recipe_quantity'));

        var multiplier = 1;
        if ($('#sub_unit_id').length) {
            var selected_multiplier = parseFloat(
                $('#sub_unit_id').find(':selected').data('multiplier')
            );
            if (!isNaN(selected_multiplier) && selected_multiplier > 0) {
                multiplier = selected_multiplier;
            }
            recipe_quantity = recipe_quantity * multiplier;
        }
        var total_ingredients_cost = 0;
        var total_input_quantity = 0;
        var total_final_quantity = 0;
        $('#ingredients_for_unit_recipe_table tbody tr').each( function() {
            if ($(this).find('.ingredient_price').length > 0) {
                var line_unit_price = parseFloat($(this).find('.ingredient_price').val()) || 0;
                var line_total_quantity = __read_number($(this).find('.total_quantities'));
                var line_multiplier = __getUnitMultiplier($(this));
                if (!line_multiplier || isNaN(line_multiplier) || line_multiplier === 0) {
                    line_multiplier = 1;
                }
                var line_waste_percent = __read_number($(this).find('.mfg_waste_percent'));

                var line_final_quantity = __substract_percent(line_total_quantity, line_waste_percent);

                var line_total = line_unit_price * line_total_quantity * line_multiplier;
                $(this).find('span.ingredient_total_price').text(__currency_trans_from_en(line_total, true));
                $(this).find('span.row_final_quantity').text(
                    __number_f(
                        line_final_quantity,
                        false,
                        false,
                        (typeof __quantity_precision !== 'undefined' ? __quantity_precision : 4)
                    )
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
                total_input_quantity += line_total_quantity;
                total_final_quantity += line_final_quantity;
            }
        });

        var qty_precision = (typeof __quantity_precision !== 'undefined' ? __quantity_precision : 4);
        $('#footer_total_input_quantity').text(__number_f(total_input_quantity, false, false, qty_precision));
        $('#footer_total_final_quantity').text(__number_f(total_final_quantity, false, false, qty_precision));
        $('#total_ingredient_price').text(__currency_trans_from_en(total_ingredients_cost, true));
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

    $(document).on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var selected_option = $(this).find(':selected');
        var multiplier = parseFloat(selected_option.data('multiplier'));
        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        var qty_element = tr.find('input.total_quantities');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
    });
</script>