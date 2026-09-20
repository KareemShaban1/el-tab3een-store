<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
		    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      <h4 class="modal-title" id="modalTitle">{{$recipe->variation->full_name}}</h4>
	    </div>
	    <div class="modal-body">
	    	<div class="row">
      			<div class="col-md-6">
      				{!! $recipe->variation->product->product_description !!}
      			</div>
      			<div class="col-md-6">
      				@foreach($recipe->variation->media as $media)
			        	{!! $media->thumbnail([60, 60], 'img-thumbnail') !!}
			        @endforeach
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-12">
      				<table class="table">
						<thead>
							<tr>
								<th>@lang('manufacturing::lang.ingredient')</th>
								<th>@lang('lang_v1.quantity')</th>
								<th>@lang('manufacturing::lang.waste_percent')</th>
								<th>@lang('lang_v1.price')</th>
							</tr>
						</thead>
						<tbody>
							@php
								$ingredient_groups = [];
								$ingredient_total_price = 0;
								$total_ingredient_quantity = 0;
							@endphp
							@foreach($ingredients as $ingredient)
								@php
									$line_multiplier = !empty($ingredient['multiplier']) ? (float) $ingredient['multiplier'] : 1;
									if ($line_multiplier <= 0) {
										$line_multiplier = 1;
									}
									$ingredient_price = $ingredient['quantity'] * $ingredient['dpp_inc_tax'] * $line_multiplier;
									$ingredient_total_price += $ingredient_price;
									$total_ingredient_quantity += (float) $ingredient['quantity'] * $line_multiplier;
								@endphp
								@if(empty($ingredient['mfg_ingredient_group_id']))
									<tr>
										<td>
											{{$ingredient['full_name']}}
										</td>
										<td><span class="display_currency" data-currency_symbol="false">{{$ingredient['quantity']}}</span> {{$ingredient['unit']}}</td>
										<td><span class="display_currency" data-currency_symbol="false">{{$ingredient['waste_percent']}}</span>%</td>
										<td><span class="display_currency" data-currency_symbol="true">{{$ingredient_price}}</span></td>
									</tr>
								@else
									@php
										$ingredient_groups[$ingredient['mfg_ingredient_group_id']][] = $ingredient;
									@endphp
								@endif	
							@endforeach
							@foreach($ingredient_groups as $ingredient_group)
								<tr>
									<td colspan="4" class="bg-gray"><strong>{{$ingredient_group[0]['ingredient_group_name'] ?? ''}}</strong> @if(!empty($ingredient_group[0]['ig_description']))
									- {{$ingredient_group[0]['ig_description']}}
								@endif</td>
								</tr>
								
								@foreach($ingredient_group as $ingredient)
									@php
										$group_multiplier = !empty($ingredient['multiplier']) ? (float) $ingredient['multiplier'] : 1;
										if ($group_multiplier <= 0) {
											$group_multiplier = 1;
										}
										$group_ingredient_price = $ingredient['quantity'] * $ingredient['dpp_inc_tax'] * $group_multiplier;
									@endphp
									<tr>
										<td>
											{{$ingredient['full_name']}}
										</td>
										<td><span class="display_currency" data-currency_symbol="false">{{$ingredient['quantity']}}</span> {{$ingredient['unit']}}</td>
										<td><span class="display_currency" data-currency_symbol="false">{{$ingredient['waste_percent']}}</span>%</td>
										<td><span class="display_currency" data-currency_symbol="true">{{$group_ingredient_price}}</span></td>
									</tr>
								@endforeach
							@endforeach
						</tbody>
						<tfoot>
							<tr class="bg-gray">
								<td class="text-right"><strong>@lang('sale.total')</strong></td>
								<td>
									<strong>
										<span class="display_currency" data-currency_symbol="false">{{$total_ingredient_quantity}}</span>
									</strong>
								</td>
								<td></td>
								<td>
									<strong>
										<span class="display_currency" data-currency_symbol="true">{{$ingredient_total_price}}</span>
									</strong>
								</td>
							</tr>
							<tr>
								<td colspan="3" class="text-right"><strong>@lang('manufacturing::lang.ingredients_cost')</strong></td>
								<td><span class="display_currency" data-currency_symbol="true">{{$ingredient_total_price}}</span></td>
							</tr>
						</tfoot>
					</table>
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-6">
      				<strong>@lang('manufacturing::lang.wastage'):</strong>
      				{{$recipe->waste_percent ?? 0}} % <br>
      				<strong>@lang('manufacturing::lang.total_output_quantity'):</strong>
      				@if(!empty($recipe->total_quantity)){{@format_quantity($recipe->total_quantity)}}@else 0 @endif @if(!empty($recipe->sub_unit)) {{$recipe->sub_unit->short_name}} @else {{$recipe->variation->product->unit->short_name}} @endif
      			</div>
      			<div class="col-md-6">
      				<strong>@lang('manufacturing::lang.extra_cost'):</strong>
      				<span ></span>{{@num_format($recipe->extra_cost)}} @if($recipe->production_cost_type == 'percentage') % @elseif ($recipe->production_cost_type == 'per_unit') (@lang('manufacturing::lang.per_unit')) @endif <br>
      				@php
      					$final_price = $ingredient_total_price;
      					$production_cost = (float) ($recipe->extra_cost ?? 0);
      					if ($recipe->production_cost_type == 'percentage') {
      						$production_cost = ($ingredient_total_price * $production_cost) / 100;
      					} elseif ($recipe->production_cost_type == 'per_unit') {
      						$production_cost = $production_cost * (float) ($recipe->total_quantity ?? 0);
      					}
      					$final_price += $production_cost;
      				@endphp
      				<strong>@lang('sale.total'):</strong>
      				<span class="display_currency" data-currency_symbol="true">{{$final_price}}</span>
      			</div>
      		</div>
      		<div class="row">
      			<div class="col-md-12">
      				<strong>@lang('lang_v1.instructions'):</strong><br>
      				{!! $recipe->instructions !!}
      			</div>
      		</div>
      	</div>
      	<div class="modal-footer">
      		<button type="button" class="btn btn-primary no-print" aria-label="Print" 
			      onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang( 'messages.print' )
			</button>
	      	<button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
	</div>
</div>
