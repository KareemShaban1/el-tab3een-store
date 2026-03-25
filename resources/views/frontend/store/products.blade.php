@extends('frontend.store.theme_layout')

@php
    $data = $payload['data'] ?? collect();
    $meta = $payload['meta'] ?? [];
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
</style>

<div class="container products-wrap">
    <aside class="card filters-card">
        <h3 class="filters-title">Filters</h3>
        <p class="filters-sub">Find products faster by search, category, and brand.</p>
        <form method="GET" action="{{ route('store.products.index') }}">
            <div class="filter-group">
                <label for="filter-q-desktop">Search</label>
                <input id="filter-q-desktop" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
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
                    <div class="products-meta">Total: {{ $meta['total'] ?? 0 }}</div>
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
        </div>
    </main>
</div>

<div id="filter-backdrop" class="sheet-backdrop"></div>
<div id="filter-sheet" class="filter-sheet">
    <div class="sheet-head">
        <h3 style="margin:0;">Filters</h3>
        <button type="button" class="sheet-close" id="close-filter-sheet">✕</button>
    </div>
    <form method="GET" action="{{ route('store.products.index') }}">
        <div class="filter-group">
            <label for="filter-q-mobile">Search</label>
            <input id="filter-q-mobile" type="text" name="q" value="{{ request('q') }}" placeholder="Product name">
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

