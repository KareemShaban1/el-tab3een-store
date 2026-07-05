@extends('layouts.app')
@section('title', __('locations_fees.locations_fees'))
@section('content')
<section class="content-header">
    <h1><i class="fa fas fa-map-marker-alt"></i> @lang('locations_fees.locations_fees')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#lf_governorates_tab" data-toggle="tab">
                            <i class="fa fas fa-map"></i> @lang('locations_fees.governorates')
                        </a>
                    </li>
                    <li>
                        <a href="#lf_cities_tab" data-toggle="tab">
                            <i class="fa fas fa-city"></i> @lang('locations_fees.cities')
                        </a>
                    </li>
                    <li>
                        <a href="#lf_areas_tab" data-toggle="tab">
                            <i class="fa fas fa-thumbtack"></i> @lang('locations_fees.areas')
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="lf_governorates_tab">
                        @include('locations_fees.governorates.index')
                    </div>
                    <div class="tab-pane" id="lf_cities_tab">
                        @include('locations_fees.cities.index')
                    </div>
                    <div class="tab-pane" id="lf_areas_tab">
                        @include('locations_fees.areas.index')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
@include('locations_fees.partials.scripts')
@endsection
