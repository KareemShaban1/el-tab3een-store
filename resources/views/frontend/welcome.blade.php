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
						<a href="#" class="btn btn-primary btn-lg">🛒 تسوق الآن</a>
						<a href="#" class="btn btn-outline-light btn-lg">🔥 عروض
							اليوم</a>
					</div>
					<div class="hero-stats">
						<div>
							<div class="h-stat-num">+50K</div>
							<div class="h-stat-lbl">منتج متوفر</div>
						</div>
						<div>
							<div class="h-stat-num">+200K</div>
							<div class="h-stat-lbl">عميل سعيد</div>
						</div>
						<div>
							<div class="h-stat-num">+500</div>
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
	<section class="cats-section section-sm">
		<div class="container">
			<div class="sec-head-row">
				<div>
					<h2 class="sec-title">تسوق حسب <span>الفئة</span></h2>
					<p class="sec-sub">اكتشف تشكيلتنا من أفضل الفئات الإلكترونية</p>
				</div>
				<a href="#" class="view-all">عرض الكل ←</a>
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
				<a href="#" class="view-all">عرض الكل ←</a>
			</div>
			<div class="products-grid" id="dynamic-products-grid"></div>
		</div>
	</section>

	<!-- ===================== FLASH DEALS ===================== -->
	<section class="flash-section section">
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
	</section>

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
	<section class="section">
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
	</section>

	<!-- ===================== TESTIMONIALS ===================== -->
	<section class="testi-section section">
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
	</section>

	<!-- ===================== NEWSLETTER ===================== -->
	<section class="news-section">
		<div class="container">
			<h2 class="news-title">📧 اشترك في نشرتنا البريدية</h2>
			<p class="news-sub">احصل على أحدث العروض والخصومات مباشرة في بريدك الإلكتروني</p>
			<form class="news-form" id="news-form">
				<input type="email" class="news-input" id="news-email"
					placeholder="أدخل بريدك الإلكتروني...">
				<button type="submit" class="news-btn">اشترك الآن</button>
			</form>
		</div>
	</section>

	@endsection