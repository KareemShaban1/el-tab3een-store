@extends('frontend.store.theme_layout')

@php
    // Controller passes `products` as a paginator whose collection was transformed to storefront-friendly arrays.
    $data = (isset($products) && $products) ? ($products->getCollection() ?? collect()) : collect();

    $activeQ = trim((string) request('q', ''));
    $activeCategoryId = request('category_id');
    $activeBrandId = request('brand_id');

    // Links should keep pagination off (remove `page`) while keeping any other filter params.
    $queryWithoutPage = request()->except(['page']);
    $queryWithoutQ = request()->except(['page', 'q']);
    $queryWithoutCategory = request()->except(['page', 'category_id']);
    $queryWithoutBrand = request()->except(['page', 'brand_id']);
@endphp

@section('content')
<style>
    .products-wrap { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 16px; padding: 30px; }
    .filters-card { position: sticky; top: 14px; height: fit-content; }
    .filters-title { margin: 0 0 10px; font-size: 20px; }
    .filters-sub { margin: 0 0 12px; color: #6b7280; font-size: 13px; }
    .filter-group { margin-bottom: 12px; }
    .filter-group label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 13px; color: #374151; }
    .filter-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

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
    .filters-card select:focus,
    #filter-sheet input[type="text"]:focus,
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
    .products-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .p-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px; background: #fff; display: grid; gap: 9px; }
    .p-img { width: 100%; height: 185px; object-fit: cover; border-radius: 10px; background: #f8fafc; }
    .p-name { margin: 0; font-size: 15px; min-height: 40px; }
    .p-meta { color: #6b7280; font-size: 12px; }
    .p-price { font-weight: 800; color: #111827; }
    .p-actions { margin-top: 2px; display: flex; gap: 8px; }
    .mobile-filter-toggle { display: none; }
    .sheet-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); opacity: 0; visibility: hidden; pointer-events: none; transition: .25s ease; z-index: 520; }
    .sheet-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .filter-sheet { position: fixed; right: 0; left: 0; bottom: 0; background: #fff; border-radius: 16px 16px 0 0; max-height: 82vh; overflow: auto; transform: translateY(105%); transition: .28s ease; z-index: 530; padding: 14px 14px 18px; box-shadow: 0 -10px 30px rgba(0,0,0,.16); }
    .filter-sheet.open { transform: translateY(0); }
    .sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .sheet-close { border: none; background: #f3f4f6; border-radius: 8px; width: 34px; height: 34px; font-weight: 700; cursor: pointer; }
    @media (max-width: 1100px) {
        .products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 860px) {
        .products-wrap { grid-template-columns: 1fr; }
        .filters-card { display: none; }
        .mobile-filter-toggle { display: inline-flex; align-items: center; gap: 6px; }
    }
    @media (max-width: 520px) {
        .products-grid { grid-template-columns: 1fr; }
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
    <aside class="card filters-card">
        <h3 class="filters-title">Filters</h3>
        <p class="filters-sub">Find products faster by search, category, and brand.</p>

        <div class="active-filters">
            @if($activeQ !== '')
                <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutQ) }}" title="Remove search filter">
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
                    <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutCategory) }}" title="Remove category filter">
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
                    <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutBrand) }}" title="Remove brand filter">
                        <span class="pill-label">Brand</span>
                        <span>{{ $activeBrandName }}</span>
                        <span class="pill-x">✕</span>
                    </a>
                @endif
            @endif
        </div>

        <form method="GET" action="{{ route('store.products.index') }}">
            <div class="filter-group">
                <label for="filter-q-desktop">Search</label>
                <div class="search-wrap">
                    <input id="filter-q-desktop" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
                    @if($activeQ !== '')
                        <a class="clear-q-btn" href="{{ route('store.products.index', $queryWithoutQ) }}" aria-label="Clear search">✕</a>
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
            <div class="filter-actions">
                <button class="btn" type="submit">Apply</button>
                <a class="btn-soft" href="{{ route('store.products.index') }}">Reset</a>
            </div>
        </form>
    </aside>

    <main>
        <div class="card">
            <div class="products-head">
                <div>
                    <h2 class="products-title">Products</h2>
                    <div class="products-meta">Total: {{ $products->total() }}</div>
                </div>
                <button type="button" class="btn mobile-filter-toggle" id="open-filter-sheet">Filter</button>
            </div>

            <div class="products-grid">
                @forelse($data as $item)
                    <div class="p-card">
                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="p-img">
                        <h3 class="p-name">{{ $item['name'] }}</h3>
                        <div class="p-meta">{{ $item['brand'] ?: 'N/A' }} | {{ $item['category'] ?: 'N/A' }}</div>
                        <div class="p-meta">Stock: {{ number_format((float)$item['in_stock_qty'], 2) }}</div>
                        <div class="p-price">Price: {{ number_format((float)$item['min_price'],2) }} - {{ number_format((float)$item['max_price'],2) }}</div>
                        <div class="p-actions">
                            <a class="btn" href="{{ route('store.products.show', $item['id']) }}">View</a>
                        </div>
                    </div>
                @empty
                    <div class="card">No products match your filters.</div>
                @endforelse
            </div>

            @if(!empty($products) && method_exists($products, 'hasPages') && $products->hasPages())
                <div style="margin-top:16px;">
                    {!! $products->links() !!}
                </div>
            @endif
        </div>
    </main>
</div>

<div id="filter-backdrop" class="sheet-backdrop"></div>
<div id="filter-sheet" class="filter-sheet">
    <div class="sheet-head">
        <h3 style="margin:0;">Filters</h3>
        <button type="button" class="sheet-close" id="close-filter-sheet">✕</button>
    </div>

    <div class="active-filters" style="margin-top:2px;margin-bottom:14px;">
        @if($activeQ !== '')
            <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutQ) }}" title="Remove search filter">
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
                <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutCategory) }}" title="Remove category filter">
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
                <a class="filter-pill" href="{{ route('store.products.index', $queryWithoutBrand) }}" title="Remove brand filter">
                    <span class="pill-label">Brand</span>
                    <span>{{ $activeBrandName }}</span>
                    <span class="pill-x">✕</span>
                </a>
            @endif
        @endif
    </div>

    <form method="GET" action="{{ route('store.products.index') }}">
        <div class="filter-group">
            <label for="filter-q-mobile">Search</label>
            <div class="search-wrap">
                <input id="filter-q-mobile" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
                @if($activeQ !== '')
                    <a class="clear-q-btn" href="{{ route('store.products.index', $queryWithoutQ) }}" aria-label="Clear search">✕</a>
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
        <div class="filter-actions">
            <button class="btn" type="submit">Apply</button>
            <a class="btn-soft" href="{{ route('store.products.index') }}">Reset</a>
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
@endsection

