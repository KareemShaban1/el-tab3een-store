@extends('frontend.store.theme_layout')

@php
    // Controller passes `products` as a paginator whose collection was transformed to storefront-friendly arrays.
    $data = (isset($products) && $products) ? ($products->getCollection() ?? collect()) : collect();

    $activeQ = trim((string) request('q', ''));
    $activeCategoryId = request('category_id');
    $activeBrandId = request('brand_id');
    $activePriceMin = request('price_min');
    $activePriceMax = request('price_max');

    // Links should keep pagination off (remove `page`) while keeping any other filter params.
    $queryWithoutPage = request()->except(['page']);
    $queryWithoutQ = request()->except(['page', 'q']);
    $queryWithoutCategory = request()->except(['page', 'category_id']);
    $queryWithoutBrand = request()->except(['page', 'brand_id']);
    $queryWithoutPrice = request()->except(['page', 'price_min', 'price_max']);

    $sPmin = (float) (isset($store_price_slider_min) ? $store_price_slider_min : 0);
    $sPmax = (float) (isset($store_price_slider_max) ? $store_price_slider_max : 1);
    $sPstep = (float) (isset($store_price_slider_step) ? $store_price_slider_step : 0.01);
    if ($sPmax <= $sPmin) {
        $sPmax = $sPmin + 1;
    }
    $sliderRngLo = $sPmin;
    $sliderRngHi = $sPmax;
    if (request()->filled('price_min')) {
        $sliderRngLo = max($sPmin, min($sPmax, (float) request('price_min')));
    }
    if (request()->filled('price_max')) {
        $sliderRngHi = max($sPmin, min($sPmax, (float) request('price_max')));
    }
    if ($sliderRngLo > $sliderRngHi) {
        [$sliderRngLo, $sliderRngHi] = [$sliderRngHi, $sliderRngLo];
    }
    $hiddenPriceMin = request()->filled('price_min') ? (string) request('price_min') : '';
    $hiddenPriceMax = request()->filled('price_max') ? (string) request('price_max') : '';
@endphp

@section('content')
@php
    $productsSeed = collect($data ?? [])->mapWithKeys(function ($item) {
        $id = (int) ($item['id'] ?? 0);
        $vars = collect($item['variations'] ?? []);
        $def = $vars->first();
        $price = $def ? (float) ($def['price_inc_tax'] ?? 0) : (float) ($item['min_price'] ?? 0);
        $vid = $def ? (int) ($def['variation_id'] ?? 0) : (int) ($item['variation_id'] ?? $id);

        return [
            $id => [
                'name' => (string) ($item['name'] ?? ''),
                'brand' => (string) ($item['brand'] ?? ''),
                'category' => (string) ($item['category'] ?? ''),
                'unit' => '',
                'price' => $price,
                'old' => null,
                'img' => (string) ($item['image_url'] ?? ''),
                'stars' => '⭐⭐⭐⭐⭐',
                'reviews' => 'متوفر',
                'variation_id' => $vid,
                'variations' => $item['variations'] ?? [],
            ],
        ];
    })->all();
@endphp
<script>
window.__SSR_STORE_PRODUCTS__ = @json($productsSeed);
</script>
<style>
    .products-wrap { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 16px; padding: 30px; }
    .filters-card { position: sticky; top: 14px; height: fit-content; }
    .filters-title { margin: 0 0 10px; font-size: 20px; }
    .filters-sub { margin: 0 0 12px; color: #6b7280; font-size: 13px; }
    .filter-group { margin-bottom: 12px; }
    .filter-group label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 13px; color: #374151; }
    .filter-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    /* Dual-thumb price range (no number inputs) */
    .filter-group--price-slider { margin-bottom: 16px; }
    .price-dual-range__values {
        font-size: 13px;
        font-weight: 800;
        color: #374151;
        margin-bottom: 6px;
        min-height: 1.25em;
    }
    /* LTR so min=left / max=right matches native range; thumbs are inset ~half width from edges */
    .price-dual-range__rail {
        position: relative;
        height: 28px;
        margin-top: 4px;
        direction: ltr;
        --price-thumb: 18px;
        --price-thumb-half: 9px;
    }
    .price-dual-range__track-bg {
        position: absolute;
        left: var(--price-thumb-half);
        right: var(--price-thumb-half);
        top: 50%;
        height: 6px;
        margin-top: -3px;
        background: #e5e7eb;
        border-radius: 999px;
        box-sizing: border-box;
    }
    .price-dual-range__fill {
        position: absolute;
        top: 50%;
        height: 6px;
        margin-top: -3px;
        left: var(--price-thumb-half);
        width: 0;
        background: rgba(234, 84, 26, 0.55);
        border-radius: 999px;
        pointer-events: none;
        z-index: 1;
        box-sizing: border-box;
    }
    .price-dual-range__input {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        width: 100%;
        height: 28px;
        margin: 0;
        background: none;
        pointer-events: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .price-dual-range__input--min { z-index: 2; }
    .price-dual-range__input--max { z-index: 3; }
    .price-dual-range__input::-webkit-slider-thumb {
        pointer-events: auto;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ea541a;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
        cursor: pointer;
    }
    .price-dual-range__input::-moz-range-thumb {
        pointer-events: auto;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ea541a;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
        cursor: pointer;
    }
    .price-dual-range__input::-webkit-slider-runnable-track {
        background: transparent;
        height: 6px;
    }
    .price-dual-range__input::-moz-range-track {
        background: transparent;
    }
    .store-products-pagination { margin-top: 16px; }
    .store-products-pagination.is-loading { opacity: .55; pointer-events: none; }

    /* Filter inputs (desktop + mobile sheet) */
    .filters-card input[type="text"],
    .filters-card select,
    #filter-sheet input[type="text"],
    #filter-sheet select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #111827;
        font-weight: 700;
        outline: none;
    }
    .filters-card input[type="text"]::placeholder,
    #filter-sheet input[type="text"]::placeholder {
        color: #9ca3af;
        font-weight: 700;
    }
    .filters-card input[type="text"]:focus,
    .filters-card input[type="number"]:focus,
    .filters-card select:focus,
    #filter-sheet input[type="text"]:focus,
    #filter-sheet input[type="number"]:focus,
    #filter-sheet select:focus {
        border-color: rgba(234,84,26,.65);
        box-shadow: 0 0 0 3px rgba(234,84,26,.12);
    }

    /* Search clear button */
    .search-wrap { position: relative; }
    .search-wrap input[type="text"] { padding-inline-end: 38px; }
    .clear-q-btn{
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        inset-inline-end: 10px;
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #6b7280;
        text-decoration: none;
        font-weight: 900;
        line-height: 1;
    }
    .clear-q-btn:hover{
        border-color: rgba(234,84,26,.55);
        background: #fff7f2;
        color: var(--accent, #ea541a);
    }

    /* Active filters pills */
    .active-filters{
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 14px;
    }
    .filter-pill{
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        line-height: 1;
    }
    .filter-pill:hover{
        border-color: rgba(234,84,26,.55);
        background: #fff7f2;
        color: var(--accent, #ea541a);
    }
    .filter-pill .pill-label{ color:#374151; }
    .filter-pill .pill-x{
        width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        background: rgba(234,84,26,.10);
        color: var(--accent, #ea541a);
        font-size: 12px;
    }
    .btn-soft { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 10px; color: #111827; text-decoration: none; font-weight: 700; background: #fff; text-align: center; }
    .btn-soft:hover { background: #f8fafc; }
    .products-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    .products-title { margin: 0; font-size: 24px; }
    .products-meta { color: #6b7280; font-size: 14px; }
    /* Match storefront .products-grid / .prod-card (see layout/head); tune inside listing card */
    [data-store-products-main] .products-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 22px;
    }
    [data-store-products-main] .prod-name a {
        color: inherit;
        text-decoration: none;
    }
    [data-store-products-main] .prod-name a:hover {
        color: var(--accent, #ea541a);
    }
    [data-store-products-main] a.pa-icon {
        text-decoration: none;
    }
    .mobile-filter-toggle { display: none; }
    .sheet-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); opacity: 0; visibility: hidden; pointer-events: none; transition: .25s ease; z-index: 520; }
    .sheet-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .filter-sheet { position: fixed; right: 0; left: 0; bottom: 0; background: #fff; border-radius: 16px 16px 0 0; max-height: 82vh; overflow: auto; transform: translateY(105%); transition: .28s ease; z-index: 530; padding: 14px 14px 18px; box-shadow: 0 -10px 30px rgba(0,0,0,.16); }
    .filter-sheet.open { transform: translateY(0); }
    .sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .sheet-close { border: none; background: #f3f4f6; border-radius: 8px; width: 34px; height: 34px; font-weight: 700; cursor: pointer; }
    @media (max-width: 1200px) {
        [data-store-products-main] .products-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
    }
    @media (max-width: 900px) {
        [data-store-products-main] .products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
    }
    @media (max-width: 860px) {
        .products-wrap { grid-template-columns: 1fr; }
        .filters-card { display: none; }
        .mobile-filter-toggle { display: inline-flex; align-items: center; gap: 6px; }
    }
    /* Small phones: keep 2 columns; tighter gap (fonts handled in layout/head @480px for .prod-*) */
    @media (max-width: 520px) {
        [data-store-products-main] .products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 8px;
        }
        [data-store-products-main] .products-title {
            font-size: clamp(1.05rem, 4.2vw, 1.35rem);
        }
        [data-store-products-main] .products-meta {
            font-size: 12px;
        }
    }

    /* Pagination UX (Laravel default "bootstrap-4" markup: nav > ul.pagination > li.page-item > a.page-link) */
    .products-wrap nav .pagination{
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        align-items: center;
        padding: 0;
        margin: 0;
        direction: rtl;
    }
    .products-wrap nav .pagination .page-item{
        list-style: none;
        margin: 0;
    }
    .products-wrap nav .pagination .page-link{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #111827;
        font-weight: 800;
        font-size: 14px;
        line-height: 1;
        transition: background .15s ease, border-color .15s ease, transform .05s ease;
    }
    .products-wrap nav .pagination .page-item a.page-link:hover{
        border-color: rgba(234,84,26,.55);
        background: #fff7f2;
        transform: translateY(-1px);
        text-decoration: none;
    }
    .products-wrap nav .pagination .page-item.active .page-link{
        border-color: rgba(234,84,26,.65);
        background: rgba(234,84,26,.10);
        color: var(--accent, #ea541a);
    }
    .products-wrap nav .pagination .page-item.disabled .page-link{
        opacity: .55;
        background: #f9fafb;
        cursor: not-allowed;
        transform: none;
    }
    .products-wrap nav .pagination .page-item:focus-within .page-link{
        outline: 3px solid rgba(234,84,26,.25);
        outline-offset: 2px;
    }
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
    padding: 0;
    margin: 0;
    direction: rtl;
}
.pagination .page-item{
    list-style: none;
    margin: 0;
}
.pagination .page-link{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #111827;
    font-weight: 800;
    font-size: 14px;
    line-height: 1;
    transition: background .15s ease, border-color .15s ease, transform .05s ease;
}
.pagination .page-item a.page-link:hover{
    border-color: rgba(234,84,26,.55);
    background: #fff7f2;
    transform: translateY(-1px);
    text-decoration: none;
}
.pagination .page-item.active .page-link{
    border-color: rgba(234,84,26,.65);
    background: rgba(234,84,26,.10);
    color: var(--accent, #ea541a);
}
.pagination .page-item.disabled .page-link{
    opacity: .55;
    background: #f9fafb;
    cursor: not-allowed;
    transform: none;
}
.pagination .page-item:focus-within .page-link{
    outline: 3px solid rgba(234,84,26,.25);
    outline-offset: 2px;
}
</style>

<div class="container products-wrap">
    <div id="store-price-slider-bounds" hidden
        data-min="{{ $sPmin }}"
        data-max="{{ $sPmax }}"
        data-step="{{ $sPstep }}"></div>

    <aside class="card filters-card">
        <h3 class="filters-title">Filters</h3>
        <p class="filters-sub">Find products faster by search, category, and brand.</p>

        <div class="active-filters js-store-active-filters">
            @if($activeQ !== '')
                <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutQ) }}" title="Remove search filter">
                    <span class="pill-label">Search</span>
                    <span>“{{ $activeQ }}”</span>
                    <span class="pill-x">✕</span>
                </a>
            @endif

            @if(! empty($activeCategoryId))
                @php
                    $activeCategoryName = '';
                    if (isset($categories)) {
                        $activeCategory = $categories->firstWhere('id', (int) $activeCategoryId);
                        $activeCategoryName = $activeCategory ? (string) $activeCategory->name : '';
                    }
                @endphp
                @if($activeCategoryName !== '')
                    <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutCategory) }}" title="Remove category filter">
                        <span class="pill-label">Category</span>
                        <span>{{ $activeCategoryName }}</span>
                        <span class="pill-x">✕</span>
                    </a>
                @endif
            @endif

            @if(! empty($activeBrandId))
                @php
                    $activeBrandName = '';
                    if (isset($brands)) {
                        $activeBrand = $brands->firstWhere('id', (int) $activeBrandId);
                        $activeBrandName = $activeBrand ? (string) $activeBrand->name : '';
                    }
                @endphp
                @if($activeBrandName !== '')
                    <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutBrand) }}" title="Remove brand filter">
                        <span class="pill-label">Brand</span>
                        <span>{{ $activeBrandName }}</span>
                        <span class="pill-x">✕</span>
                    </a>
                @endif
            @endif

            @if((string) $activePriceMin !== '' || (string) $activePriceMax !== '')
                <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutPrice) }}" title="Remove price filter">
                    <span class="pill-label">Price</span>
                    <span>
                        @if((string) $activePriceMin !== '' && (string) $activePriceMax !== '')
                            {{ $activePriceMin }} – {{ $activePriceMax }}
                        @elseif((string) $activePriceMin !== '')
                            ≥ {{ $activePriceMin }}
                        @else
                            ≤ {{ $activePriceMax }}
                        @endif
                    </span>
                    <span class="pill-x">✕</span>
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('store.products.index') }}" class="js-store-filter-form" id="store-products-filters-desktop" novalidate>
            <div class="filter-group">
                <label for="filter-q-desktop">Search</label>
                <div class="search-wrap">
                    <input id="filter-q-desktop" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
                    @if($activeQ !== '')
                        <a class="clear-q-btn js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutQ) }}" aria-label="Clear search">✕</a>
                    @endif
                </div>
            </div>
            <div class="filter-group">
                <label for="filter-category-desktop">Category</label>
                <select id="filter-category-desktop" name="category_id">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-brand-desktop">Brand</label>
                <select id="filter-brand-desktop" name="brand_id">
                    <option value="">All</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string)request('brand_id') === (string)$brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group filter-group--price-slider">
                <label>Price range</label>
                <div class="price-dual-range">
                    <div class="price-dual-range__values" id="price-slider-label-desktop"></div>
                    <div class="price-dual-range__rail">
                        <div class="price-dual-range__track-bg"></div>
                        <div class="price-dual-range__fill" id="price-fill-desktop"></div>
                        <input type="range" class="price-dual-range__input price-dual-range__input--min" id="range-price-min-desktop"
                            min="{{ $sPmin }}" max="{{ $sPmax }}" step="{{ $sPstep }}" value="{{ $sliderRngLo }}">
                        <input type="range" class="price-dual-range__input price-dual-range__input--max" id="range-price-max-desktop"
                            min="{{ $sPmin }}" max="{{ $sPmax }}" step="{{ $sPstep }}" value="{{ $sliderRngHi }}">
                    </div>
                </div>
                <input type="hidden" name="price_min" id="filter-price-min-desktop" value="{{ $hiddenPriceMin }}">
                <input type="hidden" name="price_max" id="filter-price-max-desktop" value="{{ $hiddenPriceMax }}">
            </div>
            <div class="filter-actions">
                <button class="btn" type="submit">Apply</button>
                <button type="button" class="btn-soft js-store-filters-reset">Reset</button>
            </div>
        </form>
    </aside>

    <main data-store-products-main>
        <div class="card">
            <div class="products-head">
                <div>
                    <h2 class="products-title">Products</h2>
                    <div class="products-meta">Total: {{ $products->total() }}</div>
                </div>
                <button type="button" class="btn mobile-filter-toggle" id="open-filter-sheet">Filter</button>
            </div>

            <div class="products-grid">
                @forelse($data as $idx => $item)
                    @php
                        $vars = collect($item['variations'] ?? []);
                        $defVar = $vars->first();
                        $defaultPrice = $defVar ? (float) ($defVar['price_inc_tax'] ?? 0) : (float) ($item['min_price'] ?? 0);
                        $defVid = $defVar ? (int) ($defVar['variation_id'] ?? 0) : (int) ($item['variation_id'] ?? $item['id']);
                    @endphp
                    <div class="prod-card">
                        <div class="prod-img-wrap">
                            <img class="prod-img" src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}">
                            <div class="prod-badges">
                                @if($idx < 2)
                                    <span class="badge-new">جديد</span>
                                @endif
                            </div>
                            <div class="prod-actions">
                                <button type="button" class="pa-cart"
                                    data-id="{{ (int) $item['id'] }}"
                                    data-name="{{ $item['name'] }}"
                                    data-price="{{ $defaultPrice }}"
                                    data-variation-id="{{ $defVid }}">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                        <line x1="3" y1="6" x2="21" y2="6" />
                                    </svg>
                                    أضف للسلة
                                </button>
                                <button type="button" class="pa-icon" data-quickview="{{ (int) $item['id'] }}" title="عرض سريع">👁</button>
                            </div>
                        </div>
                        <div class="prod-info">
                            <div class="prod-brand">{{ $item['brand'] ?: 'Brand' }}</div>
                            <div class="prod-name">
                                <a href="{{ route('store.products.show', $item['id']) }}" title="عرض تفاصيل المنتج">{{ $item['name'] }}</a>
                            </div>
                            <div class="stars-row"><span class="stars">⭐⭐⭐⭐⭐</span><span class="rev-count">(متوفر)</span></div>
                            @if($vars->isNotEmpty())
                                <div class="prod-variant-wrap">
                                    <select class="prod-variant" data-id="{{ (int) $item['id'] }}">
                                        @foreach($vars as $v)
                                            <option value="{{ (int) ($v['variation_id'] ?? 0) }}" data-price="{{ (float) ($v['price_inc_tax'] ?? 0) }}">
                                                {{ $v['name'] ?: 'Default' }} — {{ number_format((float) ($v['price_inc_tax'] ?? 0), 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="price-row">
                                <span class="price-now" id="prod-price-{{ (int) $item['id'] }}">{{ number_format($defaultPrice, 2) }} ج.م</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="prod-card">
                        <div class="prod-info">
                            <div class="prod-name">لا توجد منتجات مطابقة للفلاتر.</div>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="store-products-pagination">
                @if(!empty($products) && method_exists($products, 'hasPages') && $products->hasPages())
                    {!! $products->links() !!}
                @endif
            </div>
        </div>
    </main>
</div>

<div id="filter-backdrop" class="sheet-backdrop"></div>
<div id="filter-sheet" class="filter-sheet">
    <div class="sheet-head">
        <h3 style="margin:0;">Filters</h3>
        <button type="button" class="sheet-close" id="close-filter-sheet">✕</button>
    </div>

    <div class="active-filters js-store-active-filters" style="margin-top:2px;margin-bottom:14px;">
        @if($activeQ !== '')
            <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutQ) }}" title="Remove search filter">
                <span class="pill-label">Search</span>
                <span>“{{ $activeQ }}”</span>
                <span class="pill-x">✕</span>
            </a>
        @endif

        @if(! empty($activeCategoryId))
            @php
                $activeCategoryName = '';
                if (isset($categories)) {
                    $activeCategory = $categories->firstWhere('id', (int) $activeCategoryId);
                    $activeCategoryName = $activeCategory ? (string) $activeCategory->name : '';
                }
            @endphp
            @if($activeCategoryName !== '')
                <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutCategory) }}" title="Remove category filter">
                    <span class="pill-label">Category</span>
                    <span>{{ $activeCategoryName }}</span>
                    <span class="pill-x">✕</span>
                </a>
            @endif
        @endif

        @if(! empty($activeBrandId))
            @php
                $activeBrandName = '';
                if (isset($brands)) {
                    $activeBrand = $brands->firstWhere('id', (int) $activeBrandId);
                    $activeBrandName = $activeBrand ? (string) $activeBrand->name : '';
                }
            @endphp
            @if($activeBrandName !== '')
                <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutBrand) }}" title="Remove brand filter">
                    <span class="pill-label">Brand</span>
                    <span>{{ $activeBrandName }}</span>
                    <span class="pill-x">✕</span>
                </a>
            @endif
        @endif

        @if((string) $activePriceMin !== '' || (string) $activePriceMax !== '')
            <a class="filter-pill js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutPrice) }}" title="Remove price filter">
                <span class="pill-label">Price</span>
                <span>
                    @if((string) $activePriceMin !== '' && (string) $activePriceMax !== '')
                        {{ $activePriceMin }} – {{ $activePriceMax }}
                    @elseif((string) $activePriceMin !== '')
                        ≥ {{ $activePriceMin }}
                    @else
                        ≤ {{ $activePriceMax }}
                    @endif
                </span>
                <span class="pill-x">✕</span>
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('store.products.index') }}" class="js-store-filter-form" id="store-products-filters-mobile" novalidate>
        <div class="filter-group">
            <label for="filter-q-mobile">Search</label>
            <div class="search-wrap">
                <input id="filter-q-mobile" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
                @if($activeQ !== '')
                    <a class="clear-q-btn js-store-ajax-nav" href="{{ route('store.products.index', $queryWithoutQ) }}" aria-label="Clear search">✕</a>
                @endif
            </div>
        </div>
        <div class="filter-group">
            <label for="filter-category-mobile">Category</label>
            <select id="filter-category-mobile" name="category_id">
                <option value="">All</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-brand-mobile">Brand</label>
            <select id="filter-brand-mobile" name="brand_id">
                <option value="">All</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string)request('brand_id') === (string)$brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group filter-group--price-slider">
            <label>Price range</label>
            <div class="price-dual-range">
                <div class="price-dual-range__values" id="price-slider-label-mobile"></div>
                <div class="price-dual-range__rail">
                    <div class="price-dual-range__track-bg"></div>
                    <div class="price-dual-range__fill" id="price-fill-mobile"></div>
                    <input type="range" class="price-dual-range__input price-dual-range__input--min" id="range-price-min-mobile"
                        min="{{ $sPmin }}" max="{{ $sPmax }}" step="{{ $sPstep }}" value="{{ $sliderRngLo }}">
                    <input type="range" class="price-dual-range__input price-dual-range__input--max" id="range-price-max-mobile"
                        min="{{ $sPmin }}" max="{{ $sPmax }}" step="{{ $sPstep }}" value="{{ $sliderRngHi }}">
                </div>
            </div>
            <input type="hidden" name="price_min" id="filter-price-min-mobile" value="{{ $hiddenPriceMin }}">
            <input type="hidden" name="price_max" id="filter-price-max-mobile" value="{{ $hiddenPriceMax }}">
        </div>
        <div class="filter-actions">
            <button class="btn" type="submit">Apply</button>
            <button type="button" class="btn-soft js-store-filters-reset">Reset</button>
        </div>
    </form>
</div>

<script>
    (function () {
        const openBtn = document.getElementById('open-filter-sheet');
        const closeBtn = document.getElementById('close-filter-sheet');
        const sheet = document.getElementById('filter-sheet');
        const backdrop = document.getElementById('filter-backdrop');
        if (!openBtn || !closeBtn || !sheet || !backdrop) return;

        function openSheet() {
            sheet.classList.add('open');
            backdrop.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSheet() {
            sheet.classList.remove('open');
            backdrop.classList.remove('open');
            document.body.style.overflow = '';
        }

        openBtn.addEventListener('click', openSheet);
        closeBtn.addEventListener('click', closeSheet);
        backdrop.addEventListener('click', closeSheet);
    })();
</script>

<script>
    (function () {
        const main = document.querySelector('[data-store-products-main]');
        if (!main) return;

        const STORE_PRODUCTS_URL = @json(route('store.products.index'));
        const STORE_CATEGORY_NAMES = @json(isset($categories) ? $categories->pluck('name', 'id')->all() : []);
        const STORE_BRAND_NAMES = @json(isset($brands) ? $brands->pluck('name', 'id')->all() : []);

        const grid = main.querySelector('.products-grid');
        const pagWrap = main.querySelector('.store-products-pagination');
        const metaTotal = main.querySelector('.products-meta');
        const productShowTpl = @json(route('store.products.show', ['id' => '__ID__']));

        function productUrl(id) {
            return productShowTpl.replace('__ID__', String(id));
        }

        function fmtMoney(n) {
            const x = Number(n);
            if (Number.isNaN(x)) return '0.00';
            return x.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function getPriceSliderBounds() {
            const el = document.getElementById('store-price-slider-bounds');
            if (!el) {
                return { min: 0, max: 1, step: 0.01 };
            }
            return {
                min: Number(el.dataset.min),
                max: Number(el.dataset.max),
                step: Number(el.dataset.step) || 0.01,
            };
        }

        function roundToStep(n, step) {
            if (!step || step <= 0) return n;
            const k = Math.round(n / step) * step;
            return Math.round(k * 1e6) / 1e6;
        }

        function clampDualRange(suffix) {
            const b = getPriceSliderBounds();
            const lo = document.getElementById('range-price-min-' + suffix);
            const hi = document.getElementById('range-price-max-' + suffix);
            if (!lo || !hi) return;
            let vLo = roundToStep(Math.max(b.min, Math.min(b.max, Number(lo.value))), b.step);
            let vHi = roundToStep(Math.max(b.min, Math.min(b.max, Number(hi.value))), b.step);
            if (vLo > vHi) {
                if (document.activeElement === lo) vHi = vLo;
                else vLo = vHi;
            }
            lo.value = String(vLo);
            hi.value = String(vHi);
        }

        function syncPriceHiddensFromRanges(suffix) {
            const b = getPriceSliderBounds();
            const lo = document.getElementById('range-price-min-' + suffix);
            const hi = document.getElementById('range-price-max-' + suffix);
            const hMin = document.getElementById('filter-price-min-' + suffix);
            const hMax = document.getElementById('filter-price-max-' + suffix);
            if (!lo || !hi || !hMin || !hMax) return;
            const vLo = roundToStep(Number(lo.value), b.step);
            const vHi = roundToStep(Number(hi.value), b.step);
            hMin.value = vLo > b.min ? String(vLo) : '';
            hMax.value = vHi < b.max ? String(vHi) : '';
        }

        const PRICE_RANGE_THUMB_HALF_PX = 9;

        function updatePriceSliderVisual(suffix) {
            const b = getPriceSliderBounds();
            const lo = document.getElementById('range-price-min-' + suffix);
            const hi = document.getElementById('range-price-max-' + suffix);
            const fill = document.getElementById('price-fill-' + suffix);
            const lab = document.getElementById('price-slider-label-' + suffix);
            if (!lo || !hi || !fill || !lab) return;
            const rail = fill.closest('.price-dual-range__rail');
            if (!rail) return;

            const span = b.max - b.min || 1;
            const vLo = Number(lo.value);
            const vHi = Number(hi.value);
            const fracLo = Math.min(1, Math.max(0, (vLo - b.min) / span));
            const fracHi = Math.min(1, Math.max(0, (vHi - b.min) / span));

            const W = rail.getBoundingClientRect().width;
            const t = PRICE_RANGE_THUMB_HALF_PX;
            const usable = Math.max(0, W - 2 * t);
            const leftPx = t + fracLo * usable;
            const widthPx = Math.max(0, (fracHi - fracLo) * usable);

            fill.style.left = leftPx + 'px';
            fill.style.width = widthPx + 'px';

            const fullRange = vLo <= b.min && vHi >= b.max;
            if (fullRange) {
                lab.textContent = 'Any price (' + fmtMoney(b.min) + ' – ' + fmtMoney(b.max) + ')';
            } else {
                lab.textContent = fmtMoney(vLo) + ' – ' + fmtMoney(vHi);
            }
        }

        function refreshAllPriceSliderTracks() {
            ['desktop', 'mobile'].forEach((suffix) => updatePriceSliderVisual(suffix));
        }

        let priceSliderResizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(priceSliderResizeTimer);
            priceSliderResizeTimer = setTimeout(refreshAllPriceSliderTracks, 80);
        });

        function mirrorPriceSliders(fromSuffix) {
            const toSuffix = fromSuffix === 'desktop' ? 'mobile' : 'desktop';
            const loF = document.getElementById('range-price-min-' + fromSuffix);
            const hiF = document.getElementById('range-price-max-' + fromSuffix);
            const hMinF = document.getElementById('filter-price-min-' + fromSuffix);
            const hMaxF = document.getElementById('filter-price-max-' + fromSuffix);
            const loT = document.getElementById('range-price-min-' + toSuffix);
            const hiT = document.getElementById('range-price-max-' + toSuffix);
            const hMinT = document.getElementById('filter-price-min-' + toSuffix);
            const hMaxT = document.getElementById('filter-price-max-' + toSuffix);
            if (!loF || !hiF || !loT || !hiT || !hMinF || !hMaxF || !hMinT || !hMaxT) return;
            loT.value = loF.value;
            hiT.value = hiF.value;
            hMinT.value = hMinF.value;
            hMaxT.value = hMaxF.value;
            updatePriceSliderVisual(toSuffix);
        }

        function onPriceRangeInput(suffix) {
            clampDualRange(suffix);
            syncPriceHiddensFromRanges(suffix);
            updatePriceSliderVisual(suffix);
            mirrorPriceSliders(suffix);
        }

        function bindPriceSliders() {
            ['desktop', 'mobile'].forEach((suffix) => {
                ['range-price-min-' + suffix, 'range-price-max-' + suffix].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('input', () => onPriceRangeInput(suffix));
                    }
                });
            });
            ['desktop', 'mobile'].forEach((suffix) => {
                clampDualRange(suffix);
                syncPriceHiddensFromRanges(suffix);
                updatePriceSliderVisual(suffix);
            });
        }

        function resetAllPriceSliders() {
            const b = getPriceSliderBounds();
            ['desktop', 'mobile'].forEach((suffix) => {
                const lo = document.getElementById('range-price-min-' + suffix);
                const hi = document.getElementById('range-price-max-' + suffix);
                const hMi = document.getElementById('filter-price-min-' + suffix);
                const hMa = document.getElementById('filter-price-max-' + suffix);
                if (lo) lo.value = String(b.min);
                if (hi) hi.value = String(b.max);
                if (hMi) hMi.value = '';
                if (hMa) hMa.value = '';
                updatePriceSliderVisual(suffix);
            });
        }

        function syncDesktopToMobile() {
            const pairs = [
                ['filter-q-desktop', 'filter-q-mobile'],
                ['filter-category-desktop', 'filter-category-mobile'],
                ['filter-brand-desktop', 'filter-brand-mobile'],
            ];
            pairs.forEach(([a, b]) => {
                const elA = document.getElementById(a);
                const elB = document.getElementById(b);
                if (elA && elB) elB.value = elA.value;
            });
            const loF = document.getElementById('range-price-min-desktop');
            const hiF = document.getElementById('range-price-max-desktop');
            const hMinF = document.getElementById('filter-price-min-desktop');
            const hMaxF = document.getElementById('filter-price-max-desktop');
            const loT = document.getElementById('range-price-min-mobile');
            const hiT = document.getElementById('range-price-max-mobile');
            const hMinT = document.getElementById('filter-price-min-mobile');
            const hMaxT = document.getElementById('filter-price-max-mobile');
            if (loF && hiF && loT && hiT) {
                loT.value = loF.value;
                hiT.value = hiF.value;
            }
            if (hMinF && hMaxF && hMinT && hMaxT) {
                hMinT.value = hMinF.value;
                hMaxT.value = hMaxF.value;
            }
            updatePriceSliderVisual('mobile');
        }

        function syncMobileToDesktop() {
            const pairs = [
                ['filter-q-desktop', 'filter-q-mobile'],
                ['filter-category-desktop', 'filter-category-mobile'],
                ['filter-brand-desktop', 'filter-brand-mobile'],
            ];
            pairs.forEach(([a, b]) => {
                const elA = document.getElementById(a);
                const elB = document.getElementById(b);
                if (elA && elB) elA.value = elB.value;
            });
            const loF = document.getElementById('range-price-min-desktop');
            const hiF = document.getElementById('range-price-max-desktop');
            const loT = document.getElementById('range-price-min-mobile');
            const hiT = document.getElementById('range-price-max-mobile');
            if (loF && hiF && loT && hiT) {
                loF.value = loT.value;
                hiF.value = hiT.value;
            }
            clampDualRange('desktop');
            syncPriceHiddensFromRanges('desktop');
            updatePriceSliderVisual('desktop');
            const hMinF = document.getElementById('filter-price-min-desktop');
            const hMaxF = document.getElementById('filter-price-max-desktop');
            const hMinT = document.getElementById('filter-price-min-mobile');
            const hMaxT = document.getElementById('filter-price-max-mobile');
            if (hMinF && hMaxF && hMinT && hMaxT) {
                hMinF.value = hMinT.value;
                hMaxF.value = hMaxT.value;
            }
        }

        function readFiltersFromDesktopForm() {
            clampDualRange('desktop');
            syncPriceHiddensFromRanges('desktop');
            const fd = new FormData(document.getElementById('store-products-filters-desktop'));
            const p = new URLSearchParams();
            fd.forEach((v, k) => {
                if (v !== '') p.set(k, v);
            });
            return p;
        }

        function fillFormsFromParams(p) {
            const get = (k) => p.get(k) || '';
            const set = (id, v) => {
                const el = document.getElementById(id);
                if (el) el.value = v;
            };
            set('filter-q-desktop', get('q'));
            set('filter-q-mobile', get('q'));
            set('filter-category-desktop', get('category_id'));
            set('filter-category-mobile', get('category_id'));
            set('filter-brand-desktop', get('brand_id'));
            set('filter-brand-mobile', get('brand_id'));

            const b = getPriceSliderBounds();
            const pmin = get('price_min');
            const pmax = get('price_max');
            let vLo = b.min;
            let vHi = b.max;
            if (pmin !== '') vLo = Math.max(b.min, Math.min(b.max, Number(pmin)));
            if (pmax !== '') vHi = Math.max(b.min, Math.min(b.max, Number(pmax)));
            if (vLo > vHi) {
                const t = vLo;
                vLo = vHi;
                vHi = t;
            }
            ['desktop', 'mobile'].forEach((suffix) => {
                const lo = document.getElementById('range-price-min-' + suffix);
                const hi = document.getElementById('range-price-max-' + suffix);
                const hMi = document.getElementById('filter-price-min-' + suffix);
                const hMa = document.getElementById('filter-price-max-' + suffix);
                if (lo) lo.value = String(roundToStep(vLo, b.step));
                if (hi) hi.value = String(roundToStep(vHi, b.step));
                if (hMi) hMi.value = pmin;
                if (hMa) hMa.value = pmax;
                clampDualRange(suffix);
                syncPriceHiddensFromRanges(suffix);
                updatePriceSliderVisual(suffix);
            });
        }

        function buildUrlFromParams(searchParams) {
            const s = searchParams.toString();
            return s ? (STORE_PRODUCTS_URL + '?' + s) : STORE_PRODUCTS_URL;
        }

        function filtersObjectFromMeta(f) {
            const o = {};
            if (!f) return o;
            if (f.q) o.q = f.q;
            if (f.category_id) o.category_id = f.category_id;
            if (f.brand_id) o.brand_id = f.brand_id;
            if (f.price_min) o.price_min = f.price_min;
            if (f.price_max) o.price_max = f.price_max;
            return o;
        }

        function urlWithoutFilterKey(filters, key) {
            const next = { ...filters };
            delete next[key];
            const p = new URLSearchParams();
            Object.entries(next).forEach(([k, v]) => {
                if (v !== undefined && v !== null && String(v).trim() !== '') p.set(k, String(v));
            });
            return buildUrlFromParams(p);
        }

        function renderPills(filters) {
            if (!filters || Array.isArray(filters)) {
                filters = {};
            }
            const f = filtersObjectFromMeta(filters);
            const parts = [];
            if (f.q) {
                parts.push(`<a class="filter-pill js-store-ajax-nav" href="${urlWithoutFilterKey(f, 'q')}"><span class="pill-label">Search</span><span>“${String(f.q).replace(/</g, '&lt;')}</span><span class="pill-x">✕</span></a>`);
            }
            if (f.category_id) {
                const name = STORE_CATEGORY_NAMES[f.category_id] || STORE_CATEGORY_NAMES[String(f.category_id)] || '';
                if (name) {
                    parts.push(`<a class="filter-pill js-store-ajax-nav" href="${urlWithoutFilterKey(f, 'category_id')}"><span class="pill-label">Category</span><span>${String(name).replace(/</g, '&lt;')}</span><span class="pill-x">✕</span></a>`);
                }
            }
            if (f.brand_id) {
                const name = STORE_BRAND_NAMES[f.brand_id] || STORE_BRAND_NAMES[String(f.brand_id)] || '';
                if (name) {
                    parts.push(`<a class="filter-pill js-store-ajax-nav" href="${urlWithoutFilterKey(f, 'brand_id')}"><span class="pill-label">Brand</span><span>${String(name).replace(/</g, '&lt;')}</span><span class="pill-x">✕</span></a>`);
                }
            }
            if (f.price_min || f.price_max) {
                let label = '';
                if (f.price_min && f.price_max) label = `${f.price_min} – ${f.price_max}`;
                else if (f.price_min) label = `≥ ${f.price_min}`;
                else label = `≤ ${f.price_max}`;
                const fp = { ...f };
                delete fp.price_min;
                delete fp.price_max;
                const p = new URLSearchParams();
                Object.entries(fp).forEach(([k, v]) => {
                    if (v) p.set(k, String(v));
                });
                const href = p.toString() ? (STORE_PRODUCTS_URL + '?' + p.toString()) : STORE_PRODUCTS_URL;
                parts.push(`<a class="filter-pill js-store-ajax-nav" href="${href}"><span class="pill-label">Price</span><span>${String(label).replace(/</g, '&lt;')}</span><span class="pill-x">✕</span></a>`);
            }
            const html = parts.join('');
            document.querySelectorAll('.js-store-active-filters').forEach((el) => {
                el.innerHTML = html;
            });
        }

        function escHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function attrEsc(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;');
        }

        function fmtStorePrice(n) {
            const x = Number(n);
            if (Number.isNaN(x)) return '0.00 ج.م';
            return x.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م';
        }

        function syncProductsFromGridItems(items) {
            if (typeof PRODUCTS === 'undefined') return;
            (items || []).forEach((p) => {
                const variations = Array.isArray(p.variations) ? p.variations : [];
                const defaultVariant = variations[0] || null;
                const defaultPrice = Number(defaultVariant ? defaultVariant.price_inc_tax : (p.min_price || p.price || 0));
                PRODUCTS[p.id] = {
                    name: p.name,
                    brand: p.brand || '',
                    category: p.category || '',
                    unit: '',
                    price: defaultPrice,
                    old: null,
                    img: p.image_url,
                    stars: '⭐⭐⭐⭐⭐',
                    reviews: 'متوفر',
                    variation_id: defaultVariant ? Number(defaultVariant.variation_id) : Number(p.variation_id || p.id),
                    variations,
                };
            });
        }

        function renderGrid(items) {
            if (!items || !items.length) {
                grid.innerHTML = '<div class="prod-card"><div class="prod-info"><div class="prod-name">لا توجد منتجات مطابقة للفلاتر.</div></div></div>';
                return;
            }
            grid.innerHTML = items.map((p, idx) => {
                const variations = Array.isArray(p.variations) ? p.variations : [];
                const defaultVariant = variations[0] || null;
                const defaultPrice = Number(defaultVariant ? defaultVariant.price_inc_tax : (p.min_price || p.price || 0));
                const defVid = defaultVariant ? Number(defaultVariant.variation_id) : Number(p.variation_id || p.id);
                const nm = escHtml(p.name || '');
                const src = String(p.image_url || '').replace(/"/g, '');
                const brand = escHtml(p.brand || 'Brand');
                const pid = Number(p.id);
                const variantsOptions = variations.map((v) => {
                    const vid = Number(v.variation_id);
                    const pr = Number(v.price_inc_tax);
                    const vn = escHtml(v.name || 'Default');
                    return `<option value="${vid}" data-price="${pr}">${vn} — ${fmtStorePrice(pr)}</option>`;
                }).join('');
                const variantBlock = variations.length
                    ? `<div class="prod-variant-wrap"><select class="prod-variant" data-id="${pid}">${variantsOptions}</select></div>`
                    : '';
                return `
                <div class="prod-card">
                    <div class="prod-img-wrap">
                        <img class="prod-img" src="${src}" alt="${nm}">
                        <div class="prod-badges">${idx < 2 ? '<span class="badge-new">جديد</span>' : ''}</div>
                        <div class="prod-actions">
                            <button type="button" class="pa-cart" data-id="${pid}" data-name="${attrEsc(p.name || '')}" data-price="${defaultPrice}" data-variation-id="${defVid}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                    <line x1="3" y1="6" x2="21" y2="6" />
                                </svg>
                                أضف للسلة
                            </button>
                            <button type="button" class="pa-icon" data-quickview="${pid}" title="عرض سريع">👁</button>
                        </div>
                    </div>
                    <div class="prod-info">
                        <div class="prod-brand">${brand}</div>
                        <div class="prod-name"><a href="${productUrl(pid)}" title="عرض تفاصيل المنتج">${nm}</a></div>
                        <div class="stars-row"><span class="stars">⭐⭐⭐⭐⭐</span><span class="rev-count">(متوفر)</span></div>
                        ${variantBlock}
                        <div class="price-row"><span class="price-now" id="prod-price-${pid}">${fmtStorePrice(defaultPrice)}</span></div>
                    </div>
                </div>`;
            }).join('');
            syncProductsFromGridItems(items);
            if (typeof bindDynamicProductActions === 'function') {
                bindDynamicProductActions();
            }
        }

        let storeAjaxBusy = false;

        async function fetchStoreProducts(url) {
            if (storeAjaxBusy) return;
            storeAjaxBusy = true;
            if (pagWrap) pagWrap.classList.add('is-loading');
            try {
                const u = new URL(url, window.location.origin);
                const fetchUrl = u.pathname + (u.search || '');
                const res = await fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                if (!json.success) return;

                fillFormsFromParams(u.searchParams);
                syncDesktopToMobile();

                renderGrid(json.data || []);
                if (metaTotal) {
                    metaTotal.textContent = 'Total: ' + (json.meta && json.meta.total != null ? json.meta.total : 0);
                }
                if (pagWrap) {
                    pagWrap.innerHTML = json.pagination_html || '';
                    pagWrap.classList.remove('is-loading');
                }

                if (json.meta && json.meta.filters) {
                    renderPills(json.meta.filters);
                } else {
                    renderPills({});
                }

                const pathOnly = u.pathname;
                const qs = u.searchParams.toString();
                history.pushState({ storeProducts: true }, '', qs ? (pathOnly + '?' + qs) : pathOnly);
            } catch (e) {
                if (pagWrap) pagWrap.classList.remove('is-loading');
            } finally {
                storeAjaxBusy = false;
            }
        }

        document.querySelectorAll('.js-store-filter-form').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (form.id === 'store-products-filters-mobile') {
                    syncMobileToDesktop();
                } else {
                    syncDesktopToMobile();
                }
                const p = readFiltersFromDesktopForm();
                fetchStoreProducts(buildUrlFromParams(p));
                const sheet = document.getElementById('filter-sheet');
                const backdrop = document.getElementById('filter-backdrop');
                if (sheet && backdrop) {
                    sheet.classList.remove('open');
                    backdrop.classList.remove('open');
                    document.body.style.overflow = '';
                }
            });
        });

        document.querySelectorAll('.js-store-filters-reset').forEach((btn) => {
            btn.addEventListener('click', () => {
                ['filter-q-desktop', 'filter-q-mobile', 'filter-category-desktop', 'filter-category-mobile', 'filter-brand-desktop', 'filter-brand-mobile'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = '';
                });
                resetAllPriceSliders();
                fetchStoreProducts(STORE_PRODUCTS_URL);
            });
        });

        bindPriceSliders();

        const openFilterBtn = document.getElementById('open-filter-sheet');
        if (openFilterBtn) {
            openFilterBtn.addEventListener('click', () => {
                setTimeout(refreshAllPriceSliderTracks, 280);
            });
        }

        document.addEventListener('click', (e) => {
            const pill = e.target.closest('a.js-store-ajax-nav');
            if (pill && document.contains(pill)) {
                e.preventDefault();
                fetchStoreProducts(pill.href);
                return;
            }
            const pageA = e.target.closest('.store-products-pagination a.page-link');
            if (pageA && main.contains(pageA) && pageA.getAttribute('href')) {
                e.preventDefault();
                fetchStoreProducts(pageA.href);
            }
        });

        window.addEventListener('popstate', () => {
            fetchStoreProducts(window.location.href);
        });
    })();
</script>
@endsection

