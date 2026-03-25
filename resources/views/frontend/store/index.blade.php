@extends('frontend.store.theme_layout')

@section('content')
<div class="card">
    <h2>Featured Products</h2>
    <p class="muted">Business #{{ $business_id }} | Location #{{ $location_id }}</p>
</div>

<div class="grid">
    @forelse($products as $product)
        <div class="card">
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" style="width:100%;height:160px;object-fit:cover;border-radius:6px;">
            <h3>{{ $product['name'] }}</h3>
            <div class="muted">{{ $product['brand'] }} {{ $product['category'] ? ' - '.$product['category'] : '' }}</div>
            <div style="margin:10px 0;"><strong>{{ number_format((float)$product['price'], 2) }}</strong></div>
            <a class="btn" href="{{ route('store.products.show', $product['id']) }}">View</a>
        </div>
    @empty
        <div class="card">No products found.</div>
    @endforelse
</div>
@endsection

