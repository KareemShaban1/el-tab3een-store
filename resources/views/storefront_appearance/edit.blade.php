@extends('layouts.app')

@section('title', __('storefront_appearance.storefront_appearance'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('storefront_appearance.storefront_appearance')
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\StorefrontAppearanceController::class, 'update']), 'method' => 'put', 'id' => 'storefront_appearance_form']) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => __('storefront_appearance.announce_section')])
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('announce_enabled', 1, (bool) ($content['announce_enabled'] ?? true), ['class' => 'input-icheck']) !!}
                            <strong>@lang('storefront_appearance.announce_enabled')</strong>
                        </label>
                    </div>
                    <p class="help-block">@lang('storefront_appearance.announce_enabled_help')</p>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('announce_text', __('storefront_appearance.announce_text') . ':') !!}
                    {!! Form::text('announce_text', $content['announce_text'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('announce_link_text', __('storefront_appearance.announce_link_text') . ':') !!}
                    {!! Form::text('announce_link_text', $content['announce_link_text'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('announce_link_url', __('storefront_appearance.announce_link_url') . ':') !!}
                    {!! Form::text('announce_link_url', $content['announce_link_url'] ?? '', [
                        'class' => 'form-control',
                        'dir' => 'ltr',
                        'placeholder' => '/store/products',
                    ]) !!}
                    <p class="help-block">@lang('storefront_appearance.announce_link_url_help')</p>
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('storefront_appearance.contact_section')])
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('support_phone', __('storefront_appearance.support_phone') . ':') !!}
                    {!! Form::text('support_phone', $content['support_phone'] ?? '', ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('support_email', __('storefront_appearance.support_email') . ':') !!}
                    {!! Form::email('support_email', $content['support_email'] ?? '', ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('support_address', __('storefront_appearance.support_address') . ':') !!}
                    {!! Form::text('support_address', $content['support_address'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('support_hours', __('storefront_appearance.support_hours') . ':') !!}
                    {!! Form::text('support_hours', $content['support_hours'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('support_badge', __('storefront_appearance.support_badge') . ':') !!}
                    {!! Form::text('support_badge', $content['support_badge'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('storefront_appearance.brand_section')])
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('brand_name', __('storefront_appearance.brand_name') . ':') !!}
                    {!! Form::text('brand_name', $content['brand_name'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('brand_name_highlight', __('storefront_appearance.brand_name_highlight') . ':') !!}
                    {!! Form::text('brand_name_highlight', $content['brand_name_highlight'] ?? '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('brand_name_en', __('storefront_appearance.brand_name_en') . ':') !!}
                    {!! Form::text('brand_name_en', $content['brand_name_en'] ?? '', ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('footer_desc', __('storefront_appearance.footer_desc') . ':') !!}
                    {!! Form::textarea('footer_desc', $content['footer_desc'] ?? '', ['class' => 'form-control', 'rows' => 3]) !!}
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('copyright_text', __('storefront_appearance.copyright_text') . ':') !!}
                    {!! Form::text('copyright_text', $content['copyright_text'] ?? '', ['class' => 'form-control']) !!}
                    <p class="help-block">@lang('storefront_appearance.copyright_text_help')</p>
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('storefront_appearance.social_section')])
        <p class="help-block" style="margin-top:0;">@lang('storefront_appearance.social_help')</p>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('social[facebook]', __('storefront_appearance.social_facebook') . ':') !!}
                    {!! Form::text('social[facebook]', data_get($content, 'social.facebook', ''), ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('social[linkedin]', __('storefront_appearance.social_linkedin') . ':') !!}
                    {!! Form::text('social[linkedin]', data_get($content, 'social.linkedin', ''), ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('social[x]', __('storefront_appearance.social_x') . ':') !!}
                    {!! Form::text('social[x]', data_get($content, 'social.x', ''), ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('social[youtube]', __('storefront_appearance.social_youtube') . ':') !!}
                    {!! Form::text('social[youtube]', data_get($content, 'social.youtube', ''), ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('social[whatsapp]', __('storefront_appearance.social_whatsapp') . ':') !!}
                    {!! Form::text('social[whatsapp]', data_get($content, 'social.whatsapp', ''), ['class' => 'form-control', 'dir' => 'ltr']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-sm-12">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.update')
            </button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection
