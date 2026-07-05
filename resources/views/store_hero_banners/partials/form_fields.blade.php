@php
    $banner = $banner ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('badge', __('lang_v1.hero_badge') . ':') !!}
            {!! Form::text('badge', optional($banner)->badge, ['class' => 'form-control', 'placeholder' => '🔥 أحدث تقنيات 2025']) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('sort_order', __('lang_v1.sort_order') . ':') !!}
            {!! Form::number('sort_order', optional($banner)->sort_order ?? 0, ['class' => 'form-control', 'min' => 0]) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group" style="margin-top:24px;">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="is_active" value="1" {{ optional($banner)->is_active !== false ? 'checked' : '' }}>
                    @lang('business.is_active')
                </label>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    {!! Form::label('title', __('lang_v1.hero_title') . ':*') !!}
    {!! Form::textarea('title', optional($banner)->title, ['class' => 'form-control', 'rows' => 3, 'required', 'placeholder' => 'اكتشف <span>عالم التقنية</span><br>بأفضل الأسعار']) !!}
    <p class="help-block">@lang('lang_v1.hero_title_help')</p>
</div>

<div class="form-group">
    {!! Form::label('content', __('lang_v1.hero_content') . ':') !!}
    {!! Form::textarea('content', optional($banner)->content, ['class' => 'form-control', 'rows' => 3]) !!}
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('link_title', __('lang_v1.hero_link_title') . ':') !!}
            {!! Form::text('link_title', optional($banner)->link_title, ['class' => 'form-control', 'placeholder' => '🛒 تسوق الآن']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('link_url', __('lang_v1.hero_link_url') . ':') !!}
            {!! Form::text('link_url', optional($banner)->link_url, ['class' => 'form-control', 'placeholder' => route('store.products.index')]) !!}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('image', __('lang_v1.hero_image') . ':') !!}
            {!! Form::file('image', ['accept' => 'image/*', 'class' => 'form-control']) !!}
            <p class="help-block">@lang('lang_v1.hero_image_help')</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('image_alt', __('lang_v1.image_alt') . ':') !!}
            {!! Form::text('image_alt', optional($banner)->image_alt, ['class' => 'form-control']) !!}
        </div>
        @if (! empty($banner?->image_url))
            <img src="{{ $banner->image_url }}" alt="" style="max-height:120px;border-radius:8px;">
        @endif
    </div>
</div>
