@extends('layouts.app')

@section('title', __('storefront_seo.storefront_seo'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('storefront_seo.storefront_seo')
    </h1>
    <p class="help-block" style="margin-top:8px;">@lang('storefront_seo.page_help')</p>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\StorefrontSeoController::class, 'update']), 'method' => 'put', 'id' => 'storefront_seo_form']) !!}

    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    @foreach($pages as $pageKey => $page)
                        <li class="{{ $loop->first ? 'active' : '' }}">
                            <a href="#seo_page_{{ $pageKey }}" data-toggle="tab">
                                @lang($page['label_key'])
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    @foreach($pages as $pageKey => $page)
                        <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="seo_page_{{ $pageKey }}">
                            @if(!empty($page['route_hint']))
                                <p class="help-block" style="margin-top:0;">
                                    <strong>@lang('storefront_seo.route_hint'):</strong> {{ $page['route_hint'] }}
                                </p>
                            @endif
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        {!! Form::label("pages[{$pageKey}][meta_title]", __('storefront_seo.meta_title') . ':') !!}
                                        {!! Form::text("pages[{$pageKey}][meta_title]", $page['meta_title'], ['class' => 'form-control', 'maxlength' => 191]) !!}
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        {!! Form::label("pages[{$pageKey}][meta_description]", __('storefront_seo.meta_description') . ':') !!}
                                        {!! Form::textarea("pages[{$pageKey}][meta_description]", $page['meta_description'], ['class' => 'form-control', 'rows' => 2, 'maxlength' => 500]) !!}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        {!! Form::label("pages[{$pageKey}][meta_keywords]", __('storefront_seo.meta_keywords') . ':') !!}
                                        {!! Form::text("pages[{$pageKey}][meta_keywords]", $page['meta_keywords'], ['class' => 'form-control', 'maxlength' => 255]) !!}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        {!! Form::label("pages[{$pageKey}][robots]", __('storefront_seo.robots') . ':') !!}
                                        {!! Form::text("pages[{$pageKey}][robots]", $page['robots'], ['class' => 'form-control', 'maxlength' => 64, 'placeholder' => 'index,follow']) !!}
                                        <p class="help-block">@lang('storefront_seo.robots_help')</p>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        {!! Form::label("pages[{$pageKey}][og_image]", __('storefront_seo.og_image') . ':') !!}
                                        {!! Form::text("pages[{$pageKey}][og_image]", $page['og_image'], ['class' => 'form-control', 'maxlength' => 500, 'placeholder' => 'https://...']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 text-center">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.save')
            </button>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@endsection
