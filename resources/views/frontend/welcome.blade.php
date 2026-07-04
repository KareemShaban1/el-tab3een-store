@extends('frontend.store.theme_layout')

@section('content')
<!-- ===================== HERO ===================== -->
<section class="hero">
	<div class="container">
		<div class="hero-inner">

			<div class="hero-content">
				<div class="hero-badge">🔥 أحدث تقنيات 2025</div>
				<h1 class="hero-title">اكتشف <span>عالم التقنية</span><br>بأفضل
					الأسعار</h1>
				<p class="hero-desc">تشكيلة ضخمة من أحدث الأجهزة الإلكترونية من أفضل
					الماركات العالمية. جودة عالية، ضمان أصلي، وتوصيل سريع لباب
					بيتك.</p>
				<div class="hero-actions">
					<a href="{{ route('store.products.index') }}"
						class="btn btn-primary">🛒
						تسوق الآن</a>
					<!-- <a href="#" class="btn btn-outline-light">🔥 عروض
						اليوم</a> -->
				</div>
				<div class="hero-stats">
					<div>
						<div class="h-stat-num">{{ $heroStats['products'] ?? '+0' }}
						</div>
						<div class="h-stat-lbl">منتج متوفر</div>
					</div>
					<div>
						<div class="h-stat-num">
							{{ $heroStats['customers'] ?? '+0' }}</div>
						<div class="h-stat-lbl">عميل سعيد</div>
					</div>
					<div>
						<div class="h-stat-num">{{ $heroStats['brands'] ?? '+0' }}
						</div>
						<div class="h-stat-lbl">ماركة عالمية</div>
					</div>
				</div>
				<div class="hero-dots" style="margin-top:28px;">
					<button class="hero-dot active" data-dot="0"></button>
					<button class="hero-dot" data-dot="1"></button>
					<button class="hero-dot" data-dot="2"></button>
				</div>
			</div>

			<div class="hero-visual">
				<div class="hero-glow"></div>
				<img class="hero-img"
					src="https://placehold.co/460x400/3d3868/ffffff?text=iPhone+15+Pro+Max"
					alt="iPhone 15 Pro Max">
				<div class="float-badge fb1">
					<div class="fb-icon" style="background:#fff3e0;">⭐</div>
					<div>
						<strong class="fb-strong">تقييم 4.9 / 5</strong>
						<span class="fb-small">من +50,000 تقييم</span>
					</div>
				</div>
				<div class="float-badge fb2">
					<div class="fb-icon" style="background:#e8f5e9;">🚚</div>
					<div>
						<strong class="fb-strong">توصيل مجاني</strong>
						<span class="fb-small">على الطلبات +500 ج.م</span>
					</div>
				</div>
			</div>

		</div>
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
		<div class="cats-grid" id="dynamic-categories-grid"></div>
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
<section class="section tab3een-catalog-section">
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
</section>
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

@endsection