@extends('layouts.app')
@section('title', __('manufacturing::lang.packaging_profile'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>{{ $profile->name }}</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-6">
                <p><strong>@lang('manufacturing::lang.bulk_product'):</strong> {{ \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($profile->bulkVariation) }}</p>
                <p><strong>@lang('manufacturing::lang.output_product'):</strong> {{ \Modules\Manufacturing\Entities\MfgPackagingProfile::variationLabel($profile->outputVariation) }}</p>
                <p><strong>@lang('manufacturing::lang.container_type'):</strong> @lang('manufacturing::lang.' . $profile->container_type)</p>
            </div>
            <div class="col-sm-6">
                <p><strong>@lang('manufacturing::lang.units_per_carton'):</strong> {{ $profile->units_per_carton }}</p>
                <p><strong>@lang('manufacturing::lang.bulk_qty_per_container'):</strong> {{ $profile->bulk_qty_per_container }}</p>
                <p><strong>@lang('manufacturing::lang.waste_percent'):</strong> {{ $profile->waste_percent ?? 0 }}%</p>
            </div>
        </div>
        @if(!empty($profile->instructions))
            <p><strong>@lang('manufacturing::lang.instructions'):</strong> {{ $profile->instructions }}</p>
        @endif
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('manufacturing::lang.packaging_materials')])
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('manufacturing::lang.material')</th>
                    <th>@lang('manufacturing::lang.material_role')</th>
                    <th>@lang('manufacturing::lang.qty_per_container')</th>
                    <th>@lang('manufacturing::lang.qty_per_carton')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profile->materials as $material)
                    <tr>
                        <td>{{ $material->variation->full_name ?? '' }}</td>
                        <td>{{ $material->material_role }}</td>
                        <td>{{ $material->quantity_per_container }}</td>
                        <td>{{ $material->quantity_per_carton }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">@lang('manufacturing::lang.no_materials')</td></tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
</section>
@endsection
