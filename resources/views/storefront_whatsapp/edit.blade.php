@extends('layouts.app')

@section('title', __('lang_v1.storefront_whatsapp'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fab fa-whatsapp"></i> @lang('lang_v1.storefront_whatsapp')
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\StorefrontWhatsAppController::class, 'update']), 'method' => 'put', 'id' => 'storefront_whatsapp_form']) !!}
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.storefront_whatsapp')])
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('whatsapp_enabled', 1, (bool) $settings->whatsapp_enabled, ['class' => 'input-icheck']) !!}
                            <strong>@lang('lang_v1.storefront_whatsapp_enabled')</strong>
                        </label>
                    </div>
                    <p class="help-block">@lang('lang_v1.storefront_whatsapp_enabled_help')</p>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('whatsapp_number', __('lang_v1.storefront_whatsapp_number') . ':') !!}
                    {!! Form::text('whatsapp_number', $settings->whatsapp_number, [
                        'class' => 'form-control',
                        'placeholder' => '2010xxxxxxxx',
                        'dir' => 'ltr',
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.storefront_whatsapp_number_help')</p>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('whatsapp_message', __('lang_v1.storefront_whatsapp_message') . ':') !!}
                    {!! Form::textarea('whatsapp_message', $settings->whatsapp_message, [
                        'class' => 'form-control',
                        'rows' => 4,
                        'placeholder' => __('lang_v1.storefront_whatsapp_message_placeholder'),
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.storefront_whatsapp_message_help')</p>
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
