@extends('layouts.app')
@section('title', __('manufacturing::lang.edit_packaging_profile'))

@section('content')
@include('manufacturing::layouts.nav')
<section class="content-header">
    <h1>@lang('manufacturing::lang.edit_packaging_profile')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\Modules\Manufacturing\Http\Controllers\PackagingProfileController::class, 'update'], [$profile->id]), 'method' => 'put', 'id' => 'packaging_profile_form']) !!}
        @include('manufacturing::packaging_profile.partials.form')
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
            </div>
        </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
@include('manufacturing::packaging_profile.partials.form_script')
@endsection
