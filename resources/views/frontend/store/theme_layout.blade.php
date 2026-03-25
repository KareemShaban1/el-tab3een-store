<!DOCTYPE html>
<html lang="ar" dir="rtl">
@include('frontend.store.layout.head')
<body>
    <!-- ===================== ANNOUNCEMENT BAR ===================== -->
	<div class="announce">
		<div class="container">
			<span>🎉 خصم يصل لـ 50% على أحدث الهواتف الذكية! <a href="#">تسوق الآن ←</a></span>
			<div class="announce-links">
				<a href="#">تتبع طلبي</a>
				<a href="#">حسابي</a>
				<a href="#">مراكز الصيانة</a>
				<span style="color:var(--accent);font-weight:700;">📞 19900</span>
			</div>
		</div>
	</div>

	<!-- ===================== HEADER ===================== -->
	<header class="site-header">
		<div class="container">
			<div class="header-inner">

				<!-- Mobile menu trigger -->
				<button class="mob-menu-toggle h-action" style="display:none;padding:8px;"
					aria-label="القائمة">
					<svg fill="none" stroke="currentColor" stroke-width="2.2"
						viewBox="0 0 24 24">
						<path d="M3 12h18M3 6h18M3 18h18" />
					</svg>
				</button>

				<!-- Logo -->
				<a href="{{ route('welcome') }}" class="logo">
					<div class="logo-icon">⚡</div>
					<div>
						<div class="logo-name">التابعين <span>للإلكترونيات</span>
						</div>
						<div class="logo-sub">El Tab3een Electronics</div>
					</div>
				</a>

				<!-- Search -->
				<div class="header-search">
					<select class="search-cat">
						<option>كل الأقسام</option>
						<option>هواتف</option>
						<option>لابتوب</option>
						<option>إكسسوارات</option>
						<option>جيمنج</option>
						<option>تليفزيون</option>
					</select>
					<input type="text" class="search-input"
						placeholder="ابحث عن منتج، ماركة أو فئة...">
					<button class="search-btn">
						<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<circle cx="11" cy="11" r="8" />
							<path d="m21 21-4.35-4.35" />
						</svg>
					</button>
				</div>

				<!-- Actions -->
				<div class="header-actions">
					@auth('customer')
						<div class="account-dropdown">
							<button type="button" class="h-action account-toggle">
								<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
									<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
									<circle cx="12" cy="7" r="4" />
								</svg>
								<span>حسابي</span>
							</button>
							<div class="account-menu">
								<a href="{{ route('store.account.profile') }}">الملف الشخصي</a>
								<a href="{{ route('store.account.orders') }}">طلباتي</a>
								<form method="POST" action="{{ route('store.auth.logout') }}">
									@csrf
									<button type="submit">تسجيل خروج</button>
								</form>
							</div>
						</div>
					@else
						<a href="{{ route('store.auth.login.form') }}" class="h-action">
							<svg fill="none" stroke="currentColor" stroke-width="1.9"
								viewBox="0 0 24 24">
								<path
									d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
								<circle cx="12" cy="7" r="4" />
							</svg>
							<span>تسجيل دخول</span>
						</a>
					@endauth
					<button class="h-action" style="position:relative;">
						<svg fill="none" stroke="currentColor" stroke-width="1.9"
							viewBox="0 0 24 24">
							<path
								d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
						</svg>
						<span>المفضلة</span>
						<span class="h-badge wishlist-badge">3</span>
					</button>
					<button class="h-action" id="cart-open-btn"
						style="position:relative;">
						<svg fill="none" stroke="currentColor" stroke-width="1.9"
							viewBox="0 0 24 24">
							<path
								d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
							<line x1="3" y1="6" x2="21" y2="6" />
							<path d="M16 10a4 4 0 0 1-8 0" />
						</svg>
						<span>السلة</span>
						<span class="h-badge cart-badge">2</span>
					</button>
				</div>

			</div>
		</div>

		<!-- Main Nav -->
		<nav class="main-nav">
			<div class="container">
				<div class="nav-inner">

					<!-- Mega Menu Wrapper -->
					<div class="mega-wrap">
						<button class="nav-cats-btn" id="mega-toggle">
							<svg fill="none" stroke="currentColor"
								viewBox="0 0 24 24">
								<line x1="3" y1="12" x2="21" y2="12" />
								<line x1="3" y1="6" x2="21" y2="6" />
								<line x1="3" y1="18" x2="21" y2="18" />
							</svg>
							كل الأقسام
						</button>
						<div class="mega-menu" id="mega-menu">
							<div class="mega-sidebar">
								<div class="mega-sitem active">📱
									الهواتف والتابلت</div>
								<div class="mega-sitem">💻 لابتوب
									وكمبيوتر</div>
								<div class="mega-sitem">🎮 الجيمنج</div>
								<div class="mega-sitem">📺 تليفزيون
									وصوتيات</div>
								<div class="mega-sitem">🏠 المنزل الذكي
								</div>
								<div class="mega-sitem">📷 كاميرا وتصوير
								</div>
								<div class="mega-sitem">⌚ ساعات ذكية
								</div>
								<div class="mega-sitem">🔌 إكسسوارات
								</div>
							</div>
							<div class="mega-content">
								<p
									style="font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">
									الهواتف الذكية — الأكثر طلباً
								</p>
								<div class="mega-grid">
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#f0f4ff;">
											🍎</div>
										<span>آيفون</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#fff4f0;">
											📱</div>
										<span>سامسونج</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#f0fff4;">
											🔴</div>
										<span>هواوي</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#fffbf0;">
											🟠</div>
										<span>شاومي</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#f5f0ff;">
											🟢</div>
										<span>أوبو</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#fff0fb;">
											🔵</div>
										<span>فيفو</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#f0faff;">
											🟡</div>
										<span>ريلمي</span>
									</a>
									<a href="#" class="mega-item">
										<div class="mega-icon"
											style="background:#f8f0ff;">
											⚫</div>
										<span>وان بلس</span>
									</a>
									<a href="#" class="mega-item"
										style="background:var(--bg-soft);border-radius:8px;">
										<div
											class="mega-icon">
											📦</div>
										<span>كل
											الماركات</span>
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Nav Links -->
					<div class="nav-links">
						<a href="#" class="nav-link active">🏠 الرئيسية</a>
						<a href="#" class="nav-link">🛒 المتجر</a>
						<a href="#" class="nav-link">📱 هواتف</a>
						<a href="#" class="nav-link">💻 لابتوب</a>
						<a href="#" class="nav-link">🎮 جيمنج</a>
						<a href="#" class="nav-link">🔌 إكسسوارات</a>
						<a href="#" class="nav-link nav-link-hot">🔥 العروض</a>
					</div>

				</div>
			</div>
		</nav>
	</header>

	<!-- ===================== MOBILE MENU ===================== -->
	<div class="mob-menu" id="mob-menu">
		<div class="mm-head">
			<span style="font-weight:700;font-size:.95rem;">القائمة الرئيسية</span>
			<button class="mm-close" id="mob-menu-close">✕</button>
		</div>
		<a href="#" class="mm-item">🏠 الرئيسية <span>›</span></a>
		<a href="#" class="mm-item">📱 هواتف <span>›</span></a>
		<a href="#" class="mm-item">💻 لابتوب <span>›</span></a>
		<a href="#" class="mm-item">🎮 جيمنج <span>›</span></a>
		<a href="#" class="mm-item">📺 تليفزيون <span>›</span></a>
		<a href="#" class="mm-item">🏠 المنزل الذكي <span>›</span></a>
		<a href="#" class="mm-item">🔌 إكسسوارات <span>›</span></a>
		<a href="#" class="mm-item" style="color:var(--accent);font-weight:700;">🔥 العروض
			<span>›</span></a>
	</div>

	<main class="page-wrap">
	<!-- <div class="container"> -->
		@if(session('status'))
		<div class="alert {{ session('status.success') ? 'success' : 'error' }}">
			{{ session('status.msg') }}
		</div>
		@endif
		@if($errors->any())
		<div class="alert error">
			@foreach($errors->all() as $error)
			<div>{{ $error }}</div>
			@endforeach
		</div>
		@endif

		@yield('content')
	<!-- </div> -->
	</main>

    
	<!-- ===================== FOOTER ===================== -->
	<footer class="site-footer">
		<div class="container">
			<div class="footer-grid">
				<div>
					<div class="f-logo">
						<div class="logo-icon"
							style="width:40px;height:40px;font-size:1.1rem;">⚡
						</div>
						<div>
							<div class="logo-name" style="color:#fff;">
								التابعين <span>للإلكترونيات</span></div>
							<div class="logo-sub">El Tab3een Electronics</div>
						</div>
					</div>
					<p class="f-desc">وجهتك الأولى للإلكترونيات في مصر. نوفر أحدث الأجهزة
						بأفضل الأسعار مع ضمان رسمي وخدمة متميزة ما بعد البيع.</p>
					<div class="f-social">
						<div class="soc-btn">f</div>
						<div class="soc-btn">in</div>
						<div class="soc-btn">X</div>
						<div class="soc-btn">▶</div>
						<div class="soc-btn">w</div>
					</div>
				</div>
				<div>
					<div class="f-col-title">روابط سريعة</div>
					<div class="f-links">
						<a href="#" class="f-link">الصفحة الرئيسية</a>
						<a href="#" class="f-link">المتجر</a>
						<a href="#" class="f-link">🔥 العروض الحالية</a>
						<a href="#" class="f-link">المدونة التقنية</a>
						<a href="#" class="f-link">من نحن</a>
						<a href="#" class="f-link">اتصل بنا</a>
					</div>
				</div>
				<div>
					<div class="f-col-title">خدمة العملاء</div>
					<div class="f-links">
						<a href="#" class="f-link">حسابي</a>
						<a href="#" class="f-link">تتبع طلبي</a>
						<a href="#" class="f-link">سياسة الإرجاع</a>
						<a href="#" class="f-link">الضمان والصيانة</a>
						<a href="#" class="f-link">الأسئلة الشائعة</a>
						<a href="#" class="f-link">مراكز الخدمة</a>
					</div>
				</div>
				<div>
					<div class="f-col-title">تواصل معنا</div>
					<div class="f-links">
						<a href="tel:19900" class="f-link">📞 19900</a>
						<a href="mailto:info@eltab3een.com" class="f-link">✉
							info@eltab3een.com</a>
						<span class="f-link">📍 القاهرة، مصر</span>
						<span class="f-link">🕐 السبت–الخميس: 9ص–10م</span>
						<span class="f-link">🛡 ضمان أصالة المنتجات</span>
					</div>
				</div>
			</div>
			<div class="footer-bottom">
				<div class="f-copy">© 2025 التابعين للإلكترونيات. جميع الحقوق محفوظة.</div>
				<div class="pay-icons">
					<span class="pay-ic">VISA</span>
					<span class="pay-ic">MC</span>
					<span class="pay-ic">فودافون</span>
					<span class="pay-ic">فوري</span>
					<span class="pay-ic">كاش</span>
				</div>
			</div>
		</div>
	</footer>

	<!-- ===================== CART DRAWER ===================== -->
	<div class="cart-overlay" id="cart-overlay"></div>
	<div class="cart-drawer" id="cart-drawer">
		<div class="cd-header">
			<div class="cd-title">
				<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
					viewBox="0 0 24 24">
					<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
					<line x1="3" y1="6" x2="21" y2="6" />
				</svg>
				سلة التسوق
			</div>
			<button class="cd-close" id="cart-close">✕</button>
		</div>
		<div class="cd-items" id="cart-items-list"></div>
		<div class="cd-footer">
			<div class="cd-total"><span>الإجمالي:</span><span id="cart-total-val">0 ج.م</span></div>
			<button class="cd-checkout">متابعة الشراء ←</button>
			<button class="cd-continue" id="cart-continue">متابعة التسوق</button>
		</div>
	</div>

	<!-- ===================== QUICK VIEW MODAL ===================== -->
	<div class="modal-overlay" id="modal-overlay">
		<div class="modal-box">
			<div class="modal-head">
				<div class="modal-title">عرض سريع للمنتج</div>
				<button class="modal-close" id="modal-close">✕</button>
			</div>
			<div class="modal-body" id="modal-body">
				<!-- filled by JS -->
			</div>
		</div>
	</div>

	<!-- ===================== MOBILE BOTTOM NAV ===================== -->
	<nav class="mob-nav">
		<div class="mob-nav-inner">
			<button class="mob-nav-item active">
				<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
					<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
					<polyline points="9 22 9 12 15 12 15 22" />
				</svg>
				الرئيسية
			</button>
			<button class="mob-nav-item">
				<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
					<circle cx="11" cy="11" r="8" />
					<path d="m21 21-4.35-4.35" />
				</svg>
				بحث
			</button>
			<button class="mob-nav-item" id="mob-cart-btn" style="position:relative;">
				<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
					<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
					<line x1="3" y1="6" x2="21" y2="6" />
				</svg>
				السلة
				<span class="h-badge cart-badge" style="top:2px;left:2px;">2</span>
			</button>
			<button class="mob-nav-item">
				<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
					<path
						d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
				</svg>
				المفضلة
			</button>
			@auth('customer')
				<a class="mob-nav-item" href="{{ route('store.account.orders') }}">
					<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
						<circle cx="12" cy="7" r="4" />
					</svg>
					حسابي
				</a>
			@else
				<a class="mob-nav-item" href="{{ route('store.auth.login.form') }}">
					<svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
						<circle cx="12" cy="7" r="4" />
					</svg>
					دخول
				</a>
			@endauth
		</div>
	</nav>

	<!-- Toast Container -->
	<div class="toast-container" id="toast-container"></div>

	<!-- ===================== JAVASCRIPT ===================== -->
	<script>
	/* ── State ── */
	const CART_STORAGE_KEY = 'store_cart_v1';
	let cart = [];
	let wishlist = new Set([3, 5]);
	const PRODUCTS = {};
	const IS_CUSTOMER_AUTHED = "{{ auth('customer')->check() ? '1' : '0' }}" === "1";
	const STORE_LOGIN_URL = "{{ route('store.auth.login.form') }}";
	const STORE_CHECKOUT_URL = "{{ route('store.checkout') }}";
	const STORE_CHECKOUT_FORM_URL = "{{ route('store.checkout.form') }}";
	const STORE_CATEGORIES_URL = "{{ route('store.categories.index') }}";
	const STORE_FLASH_DEALS_URL = "{{ route('store.flash_deals.index') }}";

	function saveCartToStorage() {
		try {
			localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
		} catch (e) {
			// ignore storage failures
		}
	}

	function loadCartFromStorage() {
		try {
			const raw = localStorage.getItem(CART_STORAGE_KEY);
			if (!raw) return;
			const parsed = JSON.parse(raw);
			if (!Array.isArray(parsed)) return;
			cart = parsed
				.filter(i => i && Number(i.id) > 0 && Number(i.variation_id) > 0 && Number(i.qty) > 0)
				.map(i => ({
					id: Number(i.id),
					variation_id: Number(i.variation_id),
					name: String(i.name || ''),
					price: Number(i.price || 0),
					qty: Math.max(1, Number(i.qty || 1)),
					img: String(i.img || '')
				}));
		} catch (e) {
			cart = [];
		}
	}

	/* ── Helpers ── */
	const fmt = n => n.toLocaleString('ar-EG') + ' ج.م';
	const $ = id => document.getElementById(id);
	const $$ = sel => document.querySelectorAll(sel);

	/* ── Toast ── */
	function toast(msg, type = 'success') {
		const icons = {
			success: '✅',
			error: '❌',
			warn: '⚠️'
		};
		const el = document.createElement('div');
		el.className = `toast ${type}`;
		el.innerHTML = `<span>${icons[type]||'✅'}</span><span>${msg}</span>`;
		$('toast-container').prepend(el);
		setTimeout(() => {
			el.style.opacity = '0';
			el.style.transform = 'translateY(12px)';
			el.style.transition = '.25s';
			setTimeout(() => el.remove(), 300);
		}, 3200);
	}

	/* ── Cart ── */
	function openCart() {
		$('cart-overlay').classList.add('open');
		$('cart-drawer').classList.add('open');
		renderCart();
	}

	function closeCart() {
		$('cart-overlay').classList.remove('open');
		$('cart-drawer').classList.remove('open');
	}

	function addToCart(id, name, price, img, variationId = null) {
		const ex = cart.find(i => i.id === id);
		if (ex) ex.qty++;
		else cart.push({
			id,
			variation_id: variationId || id,
			name,
			price,
			qty: 1,
			img: img ||
				`https://placehold.co/80x80/F8F9FC/2D294E?text=${encodeURIComponent(name.charAt(0))}`
		});
		saveCartToStorage();
		updateBadges();
		renderCart();
	}

	async function processCheckout() {
		if (!cart.length) {
			toast('السلة فارغة', 'warn');
			return;
		}
		if (!IS_CUSTOMER_AUTHED) {
			window.location.href = STORE_LOGIN_URL;
			return;
		}
		window.location.href = STORE_CHECKOUT_FORM_URL;
	}

	function renderDynamicProducts(products) {
		const grid = $('dynamic-products-grid');
		if (!grid) return;
		if (!products.length) {
			grid.innerHTML = `<div class="prod-card"><div class="prod-info"><div class="prod-name">لا توجد منتجات متاحة حالياً</div></div></div>`;
			return;
		}

		grid.innerHTML = products.map((p, idx) => {
			const variations = Array.isArray(p.variations) ? p.variations : [];
			const defaultVariant = variations[0] || null;
			const defaultPrice = Number(defaultVariant ? defaultVariant.price_inc_tax : (p.min_price || p.price || 0));
			PRODUCTS[p.id] = {
				name: p.name,
				brand: p.brand || '',
				price: defaultPrice,
				old: null,
				img: p.image_url,
				stars: '⭐⭐⭐⭐⭐',
				reviews: 'متوفر',
				variation_id: defaultVariant ? Number(defaultVariant.variation_id) : (p.variation_id || p.id),
				variations
			};
			const variantsOptions = variations.map(v => `<option value="${Number(v.variation_id)}" data-price="${Number(v.price_inc_tax)}">${(v.name || 'Default')} - ${fmt(Number(v.price_inc_tax))}</option>`).join('');
			return `
			<div class="prod-card">
				<div class="prod-img-wrap">
					<img class="prod-img" src="${p.image_url}" alt="${p.name}">
					<div class="prod-badges">${idx < 2 ? '<span class="badge-new">جديد</span>' : ''}</div>
					<div class="prod-actions">
						<button class="pa-cart" data-id="${p.id}" data-name="${p.name}" data-price="${defaultPrice}" data-variation-id="${defaultVariant ? Number(defaultVariant.variation_id) : (p.variation_id || p.id)}">
							<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
								<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
								<line x1="3" y1="6" x2="21" y2="6" />
							</svg>
							أضف للسلة
						</button>
						<button class="pa-icon pa-wish" data-wish="${p.id}">🤍</button>
						<button class="pa-icon" data-quickview="${p.id}">👁</button>
					</div>
				</div>
				<div class="prod-info">
					<div class="prod-brand">${p.brand || 'Brand'}</div>
					<div class="prod-name">${p.name}</div>
					<div class="stars-row"><span class="stars">⭐⭐⭐⭐⭐</span><span class="rev-count">(متوفر)</span></div>
					${variations.length ? `<div style="margin-bottom:8px;"><select class="prod-variant" data-id="${p.id}" style="width:100%;padding:7px 9px;border:1px solid var(--border);border-radius:8px;font-size:.78rem;">${variantsOptions}</select></div>` : ''}
					<div class="price-row"><span class="price-now" id="prod-price-${p.id}">${fmt(defaultPrice)}</span></div>
				</div>
			</div>`;
		}).join('');

		bindDynamicProductActions();
	}

	async function loadDynamicProducts() {
		try {
			const res = await fetch('/store/products', {
				headers: {
					'Accept': 'application/json'
				}
			});
			const data = await res.json();
			if (data.success) {
				renderDynamicProducts(data.data || []);
			}
		} catch (e) {
			// no-op: keep page usable even if API fails
		}
	}

	function bindDynamicProductActions() {
		$$('.prod-variant').forEach(select => {
			select.onchange = () => {
				const productId = +(select.dataset.id || 0);
				const selectedOption = select.options[select.selectedIndex];
				const selectedVariationId = +(selectedOption?.value || 0);
				const selectedPrice = +(selectedOption?.dataset.price || 0);
				const btn = document.querySelector(`.pa-cart[data-id="${productId}"]`);
				if (btn) {
					btn.dataset.variationId = String(selectedVariationId || productId);
					btn.dataset.price = String(selectedPrice || 0);
				}
				const priceEl = $(`prod-price-${productId}`);
				if (priceEl) {
					priceEl.textContent = fmt(selectedPrice || 0);
				}
			};
		});

		$$('.pa-cart').forEach(btn => {
			btn.onclick = () => {
				const id = +btn.dataset.id;
				const name = btn.dataset.name;
				const price = +btn.dataset.price;
				const variationId = +(btn.dataset.variationId || id);
				const img = PRODUCTS[id]?.img;
				addToCart(id, name, price, img, variationId);
				toast(`تم إضافة "${name}" للسلة 🛒`);
				animBtn(btn);
			};
		});

		$$('.pa-wish').forEach(btn => {
			const id = +btn.dataset.wish;
			btn.onclick = () => toggleWish(id, btn);
		});

		$$('[data-quickview]').forEach(btn => {
			btn.onclick = () => openModal(+btn.dataset.quickview);
		});
	}

	function categoryIconByIndex(idx) {
		const icons = ['📱', '💻', '🎧', '🎮', '🏠', '📺', '⌚', '📷', '🔌', '🖨'];
		return icons[idx % icons.length];
	}

	function renderDynamicCategories(categories) {
		const grid = $('dynamic-categories-grid');
		if (!grid) return;
		if (!categories.length) {
			grid.innerHTML = `<div class="cat-card"><div class="cat-name">لا توجد فئات متاحة</div></div>`;
			return;
		}

		grid.innerHTML = categories.map((c, idx) => `
			<a href="/store/products?category_id=${c.id}" class="cat-card">
				<div class="cat-icon" style="background:#f8f9fc;">${categoryIconByIndex(idx)}</div>
				<div class="cat-name">${c.name}</div>
				<div class="cat-count">+${Number(c.count || 0).toLocaleString('ar-EG')} منتج</div>
			</a>
		`).join('');
	}

	async function loadDynamicCategories() {
		try {
			const res = await fetch(STORE_CATEGORIES_URL, {
				headers: {
					'Accept': 'application/json'
				}
			});
			const data = await res.json();
			if (data.success) {
				renderDynamicCategories(data.data || []);
			}
		} catch (e) {
			// no-op
		}
	}

	function renderDynamicFlashDeals(deals) {
		const grid = $('dynamic-flash-grid');
		if (!grid) return;
		if (!deals.length) {
			grid.innerHTML = `<div class="flash-card"><div class="flash-name">لا توجد عروض حالياً</div></div>`;
			return;
		}

		grid.innerHTML = deals.slice(0, 4).map(d => `
			<div class="flash-card">
				<img class="flash-img" src="${d.image_url}" alt="${d.name}">
				<div class="flash-name">${d.name}</div>
				<div class="flash-prices">
					<span class="flash-now">${fmt(Number(d.price || 0))}</span>
					${d.old_price ? `<span class="flash-was">${fmt(Number(d.old_price))}</span>` : ''}
				</div>
				<div class="flash-bar-wrap">
					<div class="flash-bar" style="width:${d.sold_pct}%"></div>
				</div>
				<div class="flash-sold">تم البيع ${d.sold_pct}% — باقي ${d.qty_left} قطعة!</div>
				<button class="flash-btn" data-flash-id="${d.id}" data-flash-name="${d.name}" data-flash-price="${Number(d.price || 0)}" data-flash-variation-id="${d.variation_id}">🛒 أضف للسلة</button>
			</div>
		`).join('');

		$$('.flash-btn').forEach(btn => {
			btn.onclick = () => {
				const id = +btn.dataset.flashId;
				const name = btn.dataset.flashName;
				const price = +btn.dataset.flashPrice;
				const variationId = +(btn.dataset.flashVariationId || id);
				const img = PRODUCTS[id]?.img;
				addToCart(id, name, price, img, variationId);
				toast(`تم إضافة "${name}" للسلة 🛒`);
				const orig = btn.textContent;
				btn.textContent = '✓ تمت الإضافة';
				btn.style.background = 'var(--success)';
				setTimeout(() => {
					btn.textContent = orig;
					btn.style.background = '';
				}, 1600);
			};
		});
	}

	async function loadDynamicFlashDeals() {
		try {
			const res = await fetch(STORE_FLASH_DEALS_URL, {
				headers: {
					'Accept': 'application/json'
				}
			});
			const data = await res.json();
			if (data.success) {
				(data.data || []).forEach(d => {
					PRODUCTS[d.id] = {
						name: d.name,
						brand: d.brand || '',
						price: Number(d.price || 0),
						old: d.old_price ? Number(d.old_price) : null,
						img: d.image_url,
						stars: '⭐⭐⭐⭐⭐',
						reviews: 'عرض',
						variation_id: d.variation_id
					};
				});
				renderDynamicFlashDeals(data.data || []);
			}
		} catch (e) {
			// no-op
		}
	}

	function removeFromCart(id) {
		cart = cart.filter(i => i.id !== id);
		saveCartToStorage();
		updateBadges();
		renderCart();
		toast('تم حذف المنتج من السلة', 'warn');
	}

	function updateQty(id, d) {
		const i = cart.find(i => i.id === id);
		if (i) {
			i.qty = Math.max(1, i.qty + d);
			saveCartToStorage();
			renderCart();
		}
	}

	function updateBadges() {
		const n = cart.reduce((s, i) => s + i.qty, 0);
		$$('.cart-badge').forEach(b => {
			b.textContent = n;
			b.style.display = n ? 'flex' : 'none';
		});
	}

	function renderCart() {
		const el = $('cart-items-list'),
			tot = $('cart-total-val');
		if (!el) return;
		if (!cart.length) {
			el.innerHTML =
				`<div style="text-align:center;padding:52px 24px;color:var(--muted)"><div style="font-size:3.5rem;margin-bottom:16px">🛒</div><p style="font-weight:700;color:var(--primary);margin-bottom:6px">السلة فارغة</p><p style="font-size:.85rem">أضف منتجاً للبدء</p></div>`;
			if (tot) tot.textContent = '0 ج.م';
			return;
		}
		el.innerHTML = cart.map(i => `
    <div class="cart-item">
      <img class="ci-img" src="${i.img}" alt="${i.name}">
      <div class="ci-info">
        <div class="ci-name">${i.name}</div>
        <div class="ci-price">${fmt(i.price)}</div>
        <div class="ci-qty">
          <button class="qty-btn" onclick="updateQty(${i.id},-1)">−</button>
          <span class="qty-val">${i.qty}</span>
          <button class="qty-btn" onclick="updateQty(${i.id},1)">+</button>
        </div>
      </div>
      <button class="ci-remove" onclick="removeFromCart(${i.id})">✕</button>
    </div>`).join('');
		const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
		if (tot) tot.textContent = fmt(total);
	}

	/* ── Wishlist ── */
	function toggleWish(id, btn) {
		if (wishlist.has(id)) {
			wishlist.delete(id);
			if (btn) btn.textContent = '🤍';
			toast('تم الإزالة من المفضلة', 'warn');
		} else {
			wishlist.add(id);
			if (btn) btn.textContent = '❤️';
			toast('تم الإضافة للمفضلة! ❤️');
		}
		$$('.wishlist-badge').forEach(b => {
			b.textContent = wishlist.size;
			b.style.display = wishlist.size ? 'flex' : 'none';
		});
	}

	/* ── Quick View Modal ── */
	function openModal(id) {
		const p = PRODUCTS[id];
		if (!p) return;
		const variants = Array.isArray(p.variations) ? p.variations : [];
		const defaultVariant = variants[0] || {
			variation_id: p.variation_id || id,
			price_inc_tax: p.price
		};
		const variantOptions = variants.map(v => `<option value="${Number(v.variation_id)}" data-price="${Number(v.price_inc_tax)}">${(v.name || 'Default')} - ${fmt(Number(v.price_inc_tax))}</option>`).join('');
		$('modal-body').innerHTML = `
    <img src="${p.img}" style="border-radius:12px;width:100%;aspect-ratio:1;object-fit:contain;background:var(--bg-soft);padding:16px;" alt="${p.name}">
    <div style="display:flex;flex-direction:column;justify-content:center;gap:12px;">
      <div style="font-size:.78rem;font-weight:700;color:var(--accent);text-transform:uppercase;">${p.brand}</div>
      <h3 style="font-size:1.2rem;font-weight:800;color:var(--primary);line-height:1.3;">${p.name}</h3>
      <div style="font-size:.875rem;">${p.stars} <span style="color:var(--muted);">(${p.reviews} تقييم)</span></div>
      ${variants.length ? `<select id="quick-variant" style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;font-size:.85rem;">${variantOptions}</select>` : ''}
      <div style="display:flex;align-items:baseline;gap:10px;">
        <span id="quick-price" style="font-size:1.7rem;font-weight:900;color:var(--accent);">${fmt(Number(defaultVariant.price_inc_tax || p.price))}</span>
        ${p.old?`<span style="color:var(--light);text-decoration:line-through;font-size:.9rem;">${fmt(p.old)}</span>`:''}
      </div>
      <button id="quick-add-btn" style="width:100%;padding:13px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.95rem;cursor:pointer;font-family:var(--font);margin-top:4px;transition:var(--t);" onmouseover="this.style.background='var(--accent-dark)'" onmouseout="this.style.background='var(--accent)'">
        🛒 أضف للسلة
      </button>
    </div>`;
		const quickVariant = $('quick-variant');
		const quickPrice = $('quick-price');
		quickVariant?.addEventListener('change', () => {
			const option = quickVariant.options[quickVariant.selectedIndex];
			const price = +(option?.dataset.price || 0);
			if (quickPrice) quickPrice.textContent = fmt(price);
		});
		$('quick-add-btn')?.addEventListener('click', () => {
			const selectedVariationId = +(quickVariant ? quickVariant.value : (defaultVariant.variation_id || p.variation_id || id));
			const selectedPrice = +(quickVariant ? (quickVariant.options[quickVariant.selectedIndex]?.dataset.price || defaultVariant.price_inc_tax || p.price) : (defaultVariant.price_inc_tax || p.price));
			addToCart(id, p.name, selectedPrice, p.img, selectedVariationId);
			toast('تم الإضافة للسلة! 🛒');
			closeModal();
		});
		$('modal-overlay').classList.add('open');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		$('modal-overlay').classList.remove('open');
		document.body.style.overflow = '';
	}

	/* ── Countdown ── */
	function startCountdown() {
		const end = Date.now() + 8 * 3600000 + 34 * 60000 + 22000;

		function tick() {
			const rem = Math.max(0, end - Date.now());
			const h = Math.floor(rem / 3600000),
				m = Math.floor((rem % 3600000) / 60000),
				s = Math.floor((rem % 60000) / 1000);
			const p = n => String(n).padStart(2, '0');
			const th = $('t-h'),
				tm = $('t-m'),
				ts = $('t-s');
			if (th) th.textContent = p(h);
			if (tm) tm.textContent = p(m);
			if (ts) ts.textContent = p(s);
			if (rem > 0) setTimeout(tick, 1000);
		}
		tick();
	}

	/* ── Mega Menu ── */
	function initMegaMenu() {
		const btn = $('mega-toggle'),
			menu = $('mega-menu');
		if (!btn || !menu) return;
		btn.addEventListener('click', e => {
			e.stopPropagation();
			menu.classList.toggle('open');
		});
		document.addEventListener('click', e => {
			if (!menu.contains(e.target) && e.target !== btn) menu.classList.remove(
				'open');
		});
		$$('.mega-sitem').forEach(it => it.addEventListener('click', () => {
			$$('.mega-sitem').forEach(x => x.classList.remove('active'));
			it.classList.add('active');
		}));
	}

	/* ── Hero Slider Dots ── */
	function initHeroDots() {
		const dots = $$('.hero-dot');
		if (!dots.length) return;
		let cur = 0;
		const go = i => {
			dots[cur].classList.remove('active');
			cur = i;
			dots[cur].classList.add('active');
		};
		dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
		setInterval(() => go((cur + 1) % dots.length), 3500);
	}

	/* ── Brands Auto-scroll ── */
	function initBrands() {
		const t = $('brands-track');
		if (!t) return;
		let paused = false;
		t.addEventListener('mouseenter', () => paused = true);
		t.addEventListener('mouseleave', () => paused = false);
		setInterval(() => {
			if (!paused) {
				t.scrollLeft -= 1.2;
				if (t.scrollLeft <= 0) t.scrollLeft = t.scrollWidth / 2;
			}
		}, 20);
	}

	/* ── Mobile Menu ── */
	function initMobMenu() {
		const tog = document.querySelector('.mob-menu-toggle'),
			menu = $('mob-menu'),
			cls = $('mob-menu-close'),
			overlay = $('mob-menu-overlay');
		const openMenu = () => {
			menu?.classList.add('open');
			overlay?.classList.add('open');
			document.body.style.overflow = 'hidden';
		};
		const closeMenu = () => {
			menu?.classList.remove('open');
			overlay?.classList.remove('open');
			document.body.style.overflow = '';
		};
		tog?.addEventListener('click', openMenu);
		cls?.addEventListener('click', closeMenu);
		overlay?.addEventListener('click', closeMenu);

		// Hard reset on load so menu never appears opened by default.
		closeMenu();
	}

	/* ── Newsletter ── */
	function initNewsletter() {
		$('news-form')?.addEventListener('submit', e => {
			e.preventDefault();
			const v = $('news-email')?.value || '';
			if (!v.includes('@') || !v.includes('.')) {
				toast('بريد إلكتروني غير صحيح', 'error');
				return;
			}
			toast('تم الاشتراك بنجاح! شكراً لك 🎉');
			if ($('news-email')) $('news-email').value = '';
		});
	}

	/* ── Add to cart animation ── */
	function animBtn(btn) {
		const orig = btn.innerHTML;
		btn.innerHTML = '✓ تمت الإضافة';
		btn.style.background = 'var(--success)';
		setTimeout(() => {
			btn.innerHTML = orig;
			btn.style.background = '';
		}, 1600);
	}

	/* ── DOMContentLoaded ── */
	document.addEventListener('DOMContentLoaded', () => {
		loadCartFromStorage();

		/* Cart drawer open/close */
		$('cart-open-btn')?.addEventListener('click', openCart);
		$('mob-cart-btn')?.addEventListener('click', openCart);
		$('cart-close')?.addEventListener('click', closeCart);
		$('cart-continue')?.addEventListener('click', closeCart);
		$('cart-overlay')?.addEventListener('click', e => {
			if (e.target === $('cart-overlay')) closeCart();
		});

		/* Modal close */
		$('modal-close')?.addEventListener('click', closeModal);
		$('modal-overlay')?.addEventListener('click', e => {
			if (e.target === $('modal-overlay')) closeModal();
		});

		bindDynamicProductActions();
		loadDynamicProducts();

		loadDynamicCategories();
		loadDynamicFlashDeals();

		/* Init modules */
		initMegaMenu();
		initHeroDots();
		initBrands();
		initMobMenu();
		initNewsletter();
		startCountdown();
		updateBadges();
		renderCart();
		document.querySelector('.cd-checkout')?.addEventListener('click', processCheckout);

	});

	/* Expose for inline usage */
	window.updateQty = updateQty;
	window.removeFromCart = removeFromCart;
	window.addToCart = addToCart;
	window.closeModal = closeModal;
	window.toast = toast;
	</script>
</body>

</html>