@extends('frontend.store.theme_layout')

@section('content')
<!-- ===================== HERO ===================== -->
<section class="hero" id="store-hero">
	<div class="container">
		<div class="hero-slides">
			@foreach ($heroBanners as $index => $banner)
			@php
			$linkUrl = $banner->link_url ?: route('store.products.index');
			$imageUrl = $banner->image_url ?: 'https://placehold.co/460x400/3d3868/ffffff?text=Hero';
			$imageAlt = $banner->image_alt ?: strip_tags((string) $banner->title);
			@endphp
			<div class="hero-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
				<div class="hero-inner">
					<div class="hero-content">
						@if (! empty($banner->badge))
						<div class="hero-badge">{{ $banner->badge }}</div>
						@endif
						<h1 class="hero-title">{!! $banner->title !!}</h1>
						@if (! empty($banner->content))
						<p class="hero-desc">{{ $banner->content }}</p>
						@endif
						@if (! empty($banner->link_title))
						<div class="hero-actions">
							<a href="{{ $linkUrl }}"
								class="btn btn-primary">{{ $banner->link_title }}</a>
						</div>
						@endif
					</div>

					<div class="hero-visual">
						<div class="hero-glow"></div>
						<img class="hero-img" src="{{ $imageUrl }}"
							alt="{{ $imageAlt }}">
						<div class="float-badge fb1">
							<div class="fb-icon" style="background:#fff3e0;">⭐
							</div>
							<div>
								<strong class="fb-strong">تقييم 4.9 /
									5</strong>
								<span class="fb-small">من +50,000
									تقييم</span>
							</div>
						</div>
						<div class="float-badge fb2">
							<div class="fb-icon" style="background:#e8f5e9;">
								🚚</div>
							<div>
								<strong class="fb-strong">توصيل
									مجاني</strong>
								<span class="fb-small">على الطلبات +500
									ج.م</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endforeach
		</div>

		@if ($heroBanners->count() > 1)
		<div class="hero-dots-wrap">
			<div class="hero-dots">
				@foreach ($heroBanners as $index => $banner)
				<button type="button" class="hero-dot {{ $index === 0 ? 'active' : '' }}"
					data-dot="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
				@endforeach
			</div>
		</div>
		@endif
	</div>
</section>

<!-- ===================== CATEGORIES ===================== -->
@php
$servoCategoriesForGrid = collect($tab3eenCatalog ?? [])->map(function ($category) {
return [
'id' => (int) ($category['id'] ?? 0),
'name' => (string) ($category['name'] ?? ''),
'image' => (string) ($category['image'] ?? ''),
'products' => $category['products'] ?? [],
];
})->filter(fn ($category) => $category['id'] > 0 && count($category['products']) > 0)->values();
@endphp
<script>
window.__SSR_SERVO_CATEGORIES__ = @json($servoCategoriesForGrid);
</script>
<section class="cats-section section-sm">
	<div class="container">
		<div class="sec-head-row">
			<div>
				<h2 class="sec-title">تسوق حسب <span>الفئة</span></h2>
				<p class="sec-sub">اكتشف تشكيلتنا من أفضل الفئات الإلكترونية</p>
			</div>
			<a href="{{ route('store.products.index') }}" class="view-all">عرض الكل ←</a>
		</div>
		<div class="cats-scroll" role="region" aria-label="الفئات">
			<div class="cats-grid" id="dynamic-categories-grid"></div>
		</div>
	</div>
</section>

<!-- ===================== FEATURED PRODUCTS ===================== -->
<section class="section">
	<div class="container">
		<div class="sec-head-row">
			<div>
				<h2 class="sec-title">منتجات <span>مميزة</span></h2>
				<p class="sec-sub">اختيارنا من أفضل المنتجات لهذا الأسبوع</p>
			</div>
			<a href="{{route('store.products.index')}}" class="view-all">عرض الكل ←</a>
		</div>
		<div class="products-grid" id="dynamic-products-grid"></div>
	</div>
</section>

@php
$tab3eenProductsSeed = collect($tab3eenCatalog ?? [])->flatMap(function ($category) {
return collect($category['products'] ?? [])->mapWithKeys(function ($item) use ($category) {
$id = (int) ($item['id'] ?? 0);
$vars = collect($item['variations'] ?? [])->map(function ($v) {
return [
'variation_id' => (int) ($v['variation_id'] ?? 0),
'name' => (string) ($v['name'] ?? 'Default'),
'sku' => (string) ($v['sku'] ?? ''),
'price_inc_tax' => isset($v['price']) && $v['price'] !== null ? (float) $v['price'] : null,
'qty_available' => (float) ($v['qty_available'] ?? 0),
];
})->values()->all();
$def = $vars[0] ?? null;
$price = $def && ($def['price_inc_tax'] ?? null) !== null ? (float) $def['price_inc_tax'] : null;
$vid = (int) ($item['default_variation_id'] ?? ($def['variation_id'] ?? $id));

return [
$id => [
'name' => (string) ($item['name'] ?? ''),
'brand' => (string) ($category['name'] ?? ''),
'category' => (string) ($category['name'] ?? ''),
'unit' => '',
'price' => $price,
'has_price' => ! empty($item['has_price']) && $price !== null,
'old' => null,
'img' => (string) ($item['image_url'] ?? ''),
'reviews' => 'متوفر',
'variation_id' => $vid,
'variations' => $vars,
],
];
});
})->all();
@endphp
@if (!empty($tab3eenCatalog))
<style>
.tab3een-cat-head {
	display: flex;
	align-items: center;
	gap: 16px;
}

.tab3een-cat-img {
	width: 56px;
	height: 56px;
	object-fit: contain;
	border-radius: 12px;
	background: var(--bg-soft);
	padding: 6px;
}

.tab3een-tabs {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	margin-bottom: 28px;
}

.tab3een-tab {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 18px;
	border: 1px solid var(--border);
	border-radius: 999px;
	background: var(--bg-soft);
	color: var(--primary);
	font-weight: 600;
	font-size: .9rem;
	cursor: pointer;
	transition: .2s;
}

.tab3een-tab img {
	width: 28px;
	height: 28px;
	object-fit: contain;
	border-radius: 8px;
}

.tab3een-tab.active,
.tab3een-tab:hover {
	background: var(--primary);
	color: #fff;
	border-color: var(--primary);
}

.tab3een-panel {
	display: none;
}

.tab3een-panel.active {
	display: block;
}
</style>
<script>
window.__SSR_STORE_PRODUCTS__ = Object.assign(window.__SSR_STORE_PRODUCTS__ || {}, @json($tab3eenProductsSeed));
</script>
<!-- <section class="section tab3een-catalog-section">
	<div class="container">
		<div class="sec-head-row">
			<div>
				<h2 class="sec-title">منتجات <span>التابعين</span></h2>
				<p class="sec-sub">تصفّح المنتجات حسب الفئة</p>
			</div>
		</div>

		<div class="tab3een-tabs" role="tablist">
			@foreach ($tab3eenCatalog as $index => $category)
			<button type="button" class="tab3een-tab{{ $index === 0 ? ' active' : '' }}" role="tab"
				aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
				data-tab="tab3een-cat-{{ $category['id'] }}">
				@if (!empty($category['image']))
				<img src="{{ $category['image'] }}" alt="">
				@endif
				<span>{{ $category['name'] }}</span>
			</button>
			@endforeach
		</div>

		@foreach ($tab3eenCatalog as $index => $category)
		<div class="tab3een-panel{{ $index === 0 ? ' active' : '' }}" id="tab3een-cat-{{ $category['id'] }}"
			role="tabpanel">
			<div class="products-grid">
				@foreach ($category['products'] as $product)
				@php
				$variations = collect($product['variations'] ?? []);
				$defaultVariationId = (int) ($product['default_variation_id'] ??
				$product['id']);
				$defaultVariation = $variations->firstWhere('variation_id', $defaultVariationId)
				?? $variations->first();
				$defaultPrice = $defaultVariation['price'] ?? null;
				$hasPrice = ! empty($product['has_price']) && $defaultPrice !== null;
				$defaultQty = (float) ($defaultVariation['qty_available'] ?? 0);
				@endphp
				<div class="prod-card">
					<div class="prod-img-wrap">
						<img class="prod-img"
							src="{{ $product['image_url'] ?: 'https://placehold.co/400x400/F8F9FC/2D294E?text=Product' }}"
							alt="{{ $product['name'] }}">
						<div class="prod-actions">
							@if ($hasPrice)
							<button type="button" class="pa-cart"
								data-id="{{ $product['id'] }}"
								data-name="{{ $product['name'] }}"
								data-price="{{ (float) $defaultPrice }}"
								data-variation-id="{{ $defaultVariationId }}"
								data-qty-available="{{ $defaultQty }}"
								data-source="servo">
								<svg width="14" height="14" fill="none"
									stroke="currentColor"
									stroke-width="2.5"
									viewBox="0 0 24 24">
									<path
										d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
									<line x1="3" y1="6" x2="21"
										y2="6" />
								</svg>
								أضف للسلة
							</button>
							@else
							<button type="button" class="pa-cart" disabled
								title="{{ __('storefront.catalog.price_unavailable') }}"
								style="opacity:.65;cursor:not-allowed;">
								السعر غير متاح
							</button>
							@endif
							<button type="button" class="pa-icon pa-wish"
								data-wish="{{ $product['id'] }}">🤍</button>
							<button type="button" class="pa-icon"
								data-quickview="{{ $product['id'] }}">👁</button>
						</div>
					</div>
					<div class="prod-info">
						<div class="prod-brand">{{ $category['name'] }}</div>
						<div class="prod-name">
							<a href="{{ route('store.tab3een.products.show', ['id' => $product['id']]) }}"
								title="عرض تفاصيل المنتج">{{ $product['name'] }}</a>
						</div>
						@if ($variations->count() > 1)
						<div class="prod-variant-wrap">
							<select class="prod-variant"
								data-id="{{ $product['id'] }}">
								@foreach ($variations as $variation)
								@php
								$variationPrice = $variation['price'] ??
								null;
								@endphp
								<option value="{{ (int) $variation['variation_id'] }}"
									data-price="{{ $variationPrice !== null ? (float) $variationPrice : '' }}"
									data-has-price="{{ $variationPrice !== null ? '1' : '0' }}"
									data-qty-available="{{ (float) $variation['qty_available'] }}"
									@selected((int)
									$variation['variation_id']===$defaultVariationId)>
									{{ $variation['name'] ?: 'Default' }}
									—
									@if ($variationPrice !== null)
									{{ number_format((float) $variationPrice, 2) }}
									ج.م
									@else
									السعر غير متاح
									@endif
								</option>
								@endforeach
							</select>
						</div>
						@endif
						<div class="price-row">
							<span class="price-now"
								id="prod-price-{{ $product['id'] }}">
								@if ($hasPrice)
								{{ number_format((float) $defaultPrice, 2) }}
								ج.م
								@else
								السعر غير متاح
								@endif
							</span>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
		@endforeach
	</div>
</section> -->
<script>
document.querySelectorAll('.tab3een-tab').forEach(tab => {
	tab.addEventListener('click', () => {
		const targetId = tab.dataset.tab;
		document.querySelectorAll('.tab3een-tab').forEach(t => {
			t.classList.remove('active');
			t.setAttribute('aria-selected',
				'false');
		});
		document.querySelectorAll('.tab3een-panel').forEach(p => p
			.classList.remove('active'));
		tab.classList.add('active');
		tab.setAttribute('aria-selected', 'true');
		document.getElementById(targetId)?.classList.add('active');
	});
});
</script>
@endif

<!-- ===================== FLASH DEALS ===================== -->
<!-- <section class="flash-section section">
		<div class="container">
			<div class="flash-head">
				<div class="flash-title">
					<span class="flash-icon">⚡</span>
					عروض سريعة
				</div>
				<div style="display:flex;align-items:center;gap:14px;">
					<span style="color:rgba(255,255,255,.6);font-size:.85rem;">تنتهي
						خلال:</span>
					<div class="timer">
						<div class="t-block"><span class="t-num"
								id="t-h">08</span><span
								class="t-lbl">ساعة</span></div>
						<span class="t-sep">:</span>
						<div class="t-block"><span class="t-num"
								id="t-m">34</span><span
								class="t-lbl">دقيقة</span></div>
						<span class="t-sep">:</span>
						<div class="t-block"><span class="t-num"
								id="t-s">22</span><span
								class="t-lbl">ثانية</span></div>
					</div>
				</div>
			</div>
			<div class="flash-grid" id="dynamic-flash-grid"></div>
		</div>
	</section> -->

<!-- ===================== BRANDS ===================== -->
<section class="brands-section section-sm">
	<div class="container">
		<h2 class="sec-title" style="text-align:center;margin-bottom:28px;">أفضل
			<span>الماركات</span> العالمية
		</h2>
		<div class="brands-track" id="brands-track">
			<div class="brand-tile">Apple</div>
			<div class="brand-tile">Samsung</div>
			<div class="brand-tile">Sony</div>
			<div class="brand-tile">Xiaomi</div>
			<div class="brand-tile">Huawei</div>
			<div class="brand-tile">LG</div>
			<div class="brand-tile">Dell</div>
			<div class="brand-tile">HP</div>
			<div class="brand-tile">Lenovo</div>
			<div class="brand-tile">ASUS</div>
			<div class="brand-tile">JBL</div>
			<div class="brand-tile">Anker</div>
			<div class="brand-tile">Apple</div>
			<div class="brand-tile">Samsung</div>
		</div>
	</div>
</section>

<!-- ===================== OFFER BANNER ===================== -->
<!-- <section class="section">
	<div class="container">
		<div class="offer-banner">
			<div class="offer-content">
				<div class="offer-tag">⚡ عرض محدود — ينتهي قريباً!</div>
				<h2 class="offer-title">احصل على آيباد برو M4<br>بخصم 25% حصري</h2>
				<p class="offer-desc">اغتنم هذا العرض الاستثنائي على أقوى تابلت من
					آبل. شاشة OLED Ultra Retina XDR رائعة وأداء M4 لا يُضاهى.
				</p>
				<div class="offer-btns">
					<a href="#" class="btn btn-dark btn-lg">🛒 اشتري الآن</a>
					<a href="#" class="btn btn-white-outline btn-lg">تعرف
						أكثر</a>
				</div>
			</div>
			<div class="offer-visual">
				<img src="https://placehold.co/300x220/ff7640/ffffff?text=iPad+Pro+M4"
					alt="آيباد برو M4">
			</div>
		</div>
	</div>
</section> -->

<!-- ===================== TESTIMONIALS ===================== -->
<!-- <section class="testi-section section">
		<div class="container">
			<div class="sec-head" style="text-align:center;">
				<h2 class="sec-title">ماذا يقول <span>عملاؤنا</span></h2>
				<p class="sec-sub" style="margin-top:8px;">آراء حقيقية من عملاء راضين عن تجربتهم
					معنا</p>
			</div>
			<div class="testi-grid">
				<div class="testi-card">
					<div class="testi-q">"</div>
					<p class="testi-text">تجربة تسوق رائعة! المنتجات أصلية 100%، والتوصيل
						كان في نفس اليوم. سعر آيفون 15 كان أقل بكثير من أي مكان
						تاني. هشتري منهم تاني بالتأكيد.</p>
					<div class="testi-author">
						<div class="testi-av">أم</div>
						<div>
							<div class="testi-name">أحمد محمد علي</div>
							<div class="testi-loc">📍 القاهرة &nbsp;⭐⭐⭐⭐⭐
							</div>
						</div>
					</div>
				</div>
				<div class="testi-card">
					<div class="testi-q">"</div>
					<p class="testi-text">اشتريت لابتوب ماك بوك برو وكانت تجربة ممتازة من
						بداية الطلب لحد ما وصلته. التغليف محترم جداً والجهاز أصلي
						بضمان رسمي. أنصح الكل يشتري من التابعين.</p>
					<div class="testi-author">
						<div class="testi-av">سع</div>
						<div>
							<div class="testi-name">سارة عبد العزيز</div>
							<div class="testi-loc">📍 الإسكندرية &nbsp;⭐⭐⭐⭐⭐
							</div>
						</div>
					</div>
				</div>
				<div class="testi-card">
					<div class="testi-q">"</div>
					<p class="testi-text">خدمة عملاء ممتازة وفريق محترم جداً. واجهت مشكلة
						صغيرة مع الطلب وتم حلها في نفس اليوم. المنتجات عندهم حقيقية
						وبأسعار معقولة مقارنة بالسوق.</p>
					<div class="testi-author">
						<div class="testi-av">مخ</div>
						<div>
							<div class="testi-name">محمود خالد صالح</div>
							<div class="testi-loc">📍 الجيزة &nbsp;⭐⭐⭐⭐⭐</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section> -->

<!-- ===================== NEWSLETTER ===================== -->
<!-- <section class="news-section">
		<div class="container">
			<h2 class="news-title">📧 اشترك في نشرتنا البريدية</h2>
			<p class="news-sub">احصل على أحدث العروض والخصومات مباشرة في بريدك الإلكتروني</p>
			<form class="news-form" id="news-form">
				<input type="email" class="news-input" id="news-email"
					placeholder="أدخل بريدك الإلكتروني...">
				<button type="submit" class="news-btn">اشترك الآن</button>
			</form>
		</div>
	</section> -->

@if (! empty($whatsapp['url']))
<a href="{{ $whatsapp['url'] }}" class="store-whatsapp-fab" target="_blank" rel="noopener noreferrer"
	aria-label="{{ $whatsapp['label'] }}" title="{{ $whatsapp['label'] }}">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
		<path fill="currentColor"
			d="M16.04 3C9.4 3 4 8.37 4 14.96c0 2.11.55 4.16 1.6 5.97L4 29l8.28-2.16a12.1 12.1 0 0 0 3.76.59h.01c6.64 0 12.04-5.37 12.04-11.96C28.09 8.37 22.68 3 16.04 3zm0 21.85h-.01a10.04 10.04 0 0 1-5.12-1.4l-.37-.22-4.91 1.28 1.31-4.78-.24-.39a9.88 9.88 0 0 1-1.52-5.28c0-5.47 4.49-9.92 10.02-9.92 5.53 0 10.02 4.45 10.02 9.92-.01 5.47-4.5 9.91-10.18 9.91zm5.5-7.43c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.96 1.18-.18.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.49-.9-.8-1.5-1.78-1.68-2.08-.18-.3-.02-.46.13-.61.14-.14.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.68-1.63-.93-2.23-.24-.58-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.02-1.05 2.49s1.08 2.89 1.23 3.09c.15.2 2.12 3.23 5.14 4.53.72.31 1.28.5 1.72.64.72.23 1.38.2 1.9.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.12-.27-.2-.57-.35z" />
	</svg>
	<!-- <span class="store-whatsapp-fab__label">{{ $whatsapp['label'] }}</span> -->
</a>
<style>
.store-whatsapp-fab {
	position: fixed;
	inset-inline-end: 22px;
	bottom: 22px;
	z-index: 9999;
	display: inline-flex;
	align-items: center;
	gap: 10px;
	min-height: 56px;
	padding: 0 18px 0 14px;
	border-radius: 999px;
	background: #25D366;
	color: #fff;
	text-decoration: none;
	box-shadow: 0 10px 28px rgba(37, 211, 102, .38);
	transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.store-whatsapp-fab:hover,
.store-whatsapp-fab:focus-visible {
	background: #1ebe57;
	color: #fff;
	transform: translateY(-2px);
	box-shadow: 0 14px 34px rgba(37, 211, 102, .45);
	outline: none;
}

.store-whatsapp-fab svg {
	width: 28px;
	height: 28px;
	flex-shrink: 0;
}

.store-whatsapp-fab__label {
	font-family: Cairo, sans-serif;
	font-size: 14px;
	font-weight: 700;
	line-height: 1;
	white-space: nowrap;
}

@media (max-width: 640px) {
	.store-whatsapp-fab {
		inset-inline-end: 16px;
		bottom: 75px;
		width: 56px;
		height: 56px;
		padding: 0;
		justify-content: center;
	}

	.store-whatsapp-fab__label {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		border: 0;
	}
}
</style>
@endif

@endsection