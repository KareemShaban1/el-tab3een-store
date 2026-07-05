@php
    $page = $page ?? null;
@endphp

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            {!! Form::label('title', __('store_pages.title') . ':*') !!}
            {!! Form::text('title', optional($page)->title, ['class' => 'form-control', 'required']) !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('slug', __('store_pages.slug') . ':') !!}
            {!! Form::text('slug', optional($page)->slug, ['class' => 'form-control', 'placeholder' => 'privacy-policy']) !!}
            <p class="help-block">@lang('store_pages.slug_help')</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('page_type', __('store_pages.page_type') . ':') !!}
            {!! Form::select('page_type', $page_types, optional($page)->page_type ?? \App\StorePage::PAGE_TYPE_CUSTOM, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('footer_group', __('store_pages.footer_group') . ':') !!}
            {!! Form::select('footer_group', $footer_groups, optional($page)->footer_group ?? \App\StorePage::FOOTER_GROUP_CUSTOMER_SERVICE, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('sort_order', __('lang_v1.sort_order') . ':') !!}
            {!! Form::number('sort_order', optional($page)->sort_order ?? 0, ['class' => 'form-control', 'min' => 0]) !!}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" {{ optional($page)->is_active !== false ? 'checked' : '' }}>
                @lang('business.is_active')
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="show_in_footer" value="1" {{ optional($page)->show_in_footer !== false ? 'checked' : '' }}>
                @lang('store_pages.show_in_footer')
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="show_in_header" value="1" {{ optional($page)->show_in_header ? 'checked' : '' }}>
                @lang('store_pages.show_in_header')
            </label>
        </div>
    </div>
</div>

<div class="form-group">
    {!! Form::label('excerpt', __('store_pages.excerpt') . ':') !!}
    {!! Form::textarea('excerpt', optional($page)->excerpt, ['class' => 'form-control', 'rows' => 2]) !!}
</div>

<div class="form-group">
    {!! Form::label('content', __('store_pages.content') . ':') !!}
    {!! Form::textarea('content', optional($page)->content, ['class' => 'form-control', 'id' => 'store_page_content', 'rows' => 8]) !!}
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('meta_title', __('store_pages.meta_title') . ':') !!}
            {!! Form::text('meta_title', optional($page)->meta_title, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('meta_description', __('store_pages.meta_description') . ':') !!}
            {!! Form::textarea('meta_description', optional($page)->meta_description, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
    </div>
</div>
