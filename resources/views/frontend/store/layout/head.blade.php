<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'El Tab3een Store' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
			/* ============================================================
		DESIGN SYSTEM
		============================================================ */
	:root {
		--primary: #2D294E;
		--primary-light: #3d3868;
		--primary-dark: #1e1b35;
		--accent: #EA541A;
		--accent-dark: #c43e0e;
		--bg: #FFFFFF;
		--bg-soft: #F8F9FC;
		--text: #1a1a2e;
		--muted: #6b7280;
		--light: #9ca3af;
		--border: #e5e7eb;
		--success: #10b981;
		--warning: #f59e0b;
		--danger: #ef4444;
		--shadow-sm: 0 1px 4px rgba(45, 41, 78, .07);
		--shadow-md: 0 4px 20px rgba(45, 41, 78, .10);
		--shadow-lg: 0 10px 40px rgba(45, 41, 78, .14);
		--shadow-xl: 0 24px 64px rgba(45, 41, 78, .18);
		--r: 12px;
		--r-sm: 8px;
		--r-lg: 20px;
		--r-full: 9999px;
		--t: all .25s cubic-bezier(.4, 0, .2, 1);
		--font: 'Cairo', 'Tajawal', 'Noto Kufi Arabic', Tahoma, Arial, sans-serif;
	}

	/* ---- Reset ---- */
	*,
	*::before,
	*::after {
		box-sizing: border-box;
		margin: 0;
		padding: 0
	}

	html {
		direction: rtl;
		scroll-behavior: smooth;
		width: 100%;
		max-width: 100%;
		overflow-x: hidden;
	}

	body {
		font-family: var(--font);
		background: var(--bg);
		color: var(--text);
		line-height: 1.6;
		overflow-x: hidden;
		width: 100%;
		max-width: 100%;
		position: relative;
	}

	a {
		text-decoration: none;
		color: inherit;
		transition: var(--t)
	}

	img {
		max-width: 100%;
		display: block
	}

	ul {
		list-style: none
	}

	button {
		cursor: pointer;
		font-family: var(--font);
		border: none;
		outline: none
	}

	input,
	select {
		font-family: var(--font);
		outline: none
	}

	/* ---- Layout ---- */
	.container {
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 24px
	}

	.section {
		padding: 72px 0
	}

	.section-sm {
		padding: 44px 0
	}

	/* ---- Buttons ---- */
	.btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 13px 26px;
		border-radius: var(--r-sm);
		font-weight: 700;
		font-size: .95rem;
		font-family: var(--font);
		transition: var(--t);
		cursor: pointer;
		border: 2px solid transparent;
		white-space: nowrap
	}

	.btn-primary {
		background: var(--accent);
		color: #fff;
		border-color: var(--accent)
	}

	.btn-primary:hover {
		background: var(--accent-dark);
		border-color: var(--accent-dark);
		transform: translateY(-2px);
		box-shadow: 0 8px 28px rgba(234, 84, 26, .38)
	}

	.btn-outline-light {
		background: transparent;
		color: #fff;
		border-color: rgba(255, 255, 255, .55)
	}

	.btn-outline-light:hover {
		background: #fff;
		color: var(--primary)
	}

	.btn-lg {
		padding: 15px 36px;
		font-size: 1rem
	}

	/* ---- Section headers ---- */
	.sec-head {
		margin-bottom: 40px
	}

	.sec-head-row {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		margin-bottom: 40px
	}

	.sec-title {
		font-size: 2rem;
		font-weight: 800;
		color: var(--primary);
		line-height: 1.2
	}

	.sec-title span {
		color: var(--accent)
	}

	.sec-sub {
		color: var(--muted);
		margin-top: 6px;
		font-size: .95rem
	}

	.view-all {
		color: var(--accent);
		font-weight: 700;
		font-size: .875rem;
		display: flex;
		align-items: center;
		gap: 5px;
		transition: var(--t);
		white-space: nowrap
	}

	.view-all:hover {
		gap: 9px
	}

/* auth theme layout */
	.auth-card {
            max-width: 620px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r);
            box-shadow: var(--shadow-md);
            padding: 24px;
        }
        .auth-title { margin: 0 0 8px; color: var(--primary); font-size: 28px; }
        .auth-sub { margin: 0 0 18px; color: var(--muted); font-size: 14px; }
        label { display: block; font-weight: 700; margin-bottom: 6px; }
        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            padding: 11px 12px;
            margin-bottom: 14px;
            font-size: 14px;
        }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
        .alert {
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        @media (max-width: 700px) {
            .row { grid-template-columns: 1fr; }
            .auth-title { font-size: 23px; }
        }


	/* ============================================================
   ANNOUNCEMENT BAR
   ============================================================ */
	.announce {
		background: var(--primary);
		color: rgba(255, 255, 255, .85);
		padding: 9px 0;
		font-size: .82rem
	}

	.announce .container {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px
	}

	.announce a {
		color: var(--accent);
		font-weight: 700
	}

	.announce-links {
		display: flex;
		gap: 18px
	}

	.announce-links a {
		color: rgba(255, 255, 255, .7);
		transition: var(--t)
	}

	.announce-links a:hover {
		color: #fff
	}

	/* ============================================================
   HEADER
   ============================================================ */
	.site-header {
		background: #fff;
		box-shadow: 0 2px 20px rgba(0, 0, 0, .055);
		position: sticky;
		top: 0;
		z-index: 90
	}

	.header-inner {
		display: flex;
		align-items: center;
		gap: 20px;
		padding: 14px 0
	}

	/* Logo */
	.logo {
		display: flex;
		align-items: center;
		gap: 11px;
		flex-shrink: 0
	}

	.logo-icon {
		width: 46px;
		height: 46px;
		background: linear-gradient(135deg, var(--primary), var(--primary-light));
		border-radius: 11px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.35rem;
		color: #fff;
		box-shadow: 0 4px 14px rgba(45, 41, 78, .28);
		flex-shrink: 0
	}

	.logo-name {
		font-size: 1.05rem;
		font-weight: 800;
		color: var(--primary);
		line-height: 1.15
	}

	.logo-name span {
		color: var(--accent)
	}

	.logo-sub {
		font-size: .68rem;
		color: var(--light);
		font-weight: 500;
		letter-spacing: .3px
	}

	/* Search */
	.header-search {
		flex: 1;
		display: flex;
		background: var(--bg-soft);
		border: 2px solid var(--border);
		border-radius: var(--r-full);
		overflow: hidden;
		transition: var(--t)
	}

	.header-search:focus-within {
		border-color: var(--accent);
		box-shadow: 0 0 0 4px rgba(234, 84, 26, .1)
	}

	.search-cat {
		padding: 0 14px;
		background: #eef0f5;
		border: none;
		border-left: 2px solid var(--border);
		color: var(--muted);
		font-size: .82rem;
		cursor: pointer;
		white-space: nowrap
	}

	.search-input {
		flex: 1;
		padding: 11px 18px;
		background: transparent;
		border: none;
		font-size: .93rem;
		color: var(--text)
	}

	.search-input::placeholder {
		color: var(--light)
	}

	.search-btn {
		padding: 11px 20px;
		background: var(--accent);
		border: none;
		color: #fff;
		cursor: pointer;
		transition: var(--t);
		display: flex;
		align-items: center
	}

	.search-btn:hover {
		background: var(--accent-dark)
	}

	.search-btn svg {
		width: 18px;
		height: 18px;
		stroke-width: 2.5
	}

	/* Header Actions */
	.header-actions {
		display: flex;
		align-items: center;
		gap: 2px;
		flex-shrink: 0
	}

	.h-action {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 2px;
		padding: 8px 11px;
		border-radius: var(--r-sm);
		color: var(--text);
		font-size: .7rem;
		font-weight: 600;
		transition: var(--t);
		background: none;
		border: none;
		cursor: pointer;
		font-family: var(--font);
		position: relative;
		white-space: nowrap
	}

	.h-action:hover {
		color: var(--accent);
		background: var(--bg-soft)
	}

	.h-action svg {
		width: 21px;
		height: 21px
	}

	.mob-menu-toggle {
		display: none;
	}

	.h-badge {
		position: absolute;
		top: 3px;
		left: 4px;
		background: var(--accent);
		color: #fff;
		font-size: .62rem;
		font-weight: 800;
		width: 17px;
		height: 17px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 2px solid #fff
	}

	.account-dropdown {
		position: relative;
	}

	.account-toggle {
		min-width: 76px;
	}

	.account-menu {
		position: absolute;
		top: calc(100% + 8px);
		right: 0;
		min-width: 190px;
		background: #fff;
		border: 1px solid var(--border);
		border-radius: 12px;
		box-shadow: var(--shadow-md);
		padding: 6px;
		opacity: 0;
		visibility: hidden;
		transform: translateY(6px);
		transition: var(--t);
		z-index: 220;
	}

	.account-dropdown:hover .account-menu,
	.account-dropdown:focus-within .account-menu {
		opacity: 1;
		visibility: visible;
		transform: translateY(0);
	}

	.account-menu a,
	.account-menu button {
		width: 100%;
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 9px 10px;
		border-radius: 8px;
		border: none;
		background: transparent;
		color: var(--text);
		text-decoration: none;
		font-size: .82rem;
		font-weight: 700;
		font-family: var(--font);
		cursor: pointer;
		text-align: right;
	}

	.account-menu a:hover,
	.account-menu button:hover {
		background: var(--bg-soft);
		color: var(--accent);
	}

	/* ============================================================
   MAIN NAV
   ============================================================ */
	.main-nav {
		background: var(--primary);
		border-top: 1px solid rgba(255, 255, 255, .07)
	}

	.nav-inner {
		display: flex;
		align-items: stretch
	}

	/* All-categories button */
	.nav-cats-btn {
		display: flex;
		align-items: center;
		gap: 9px;
		padding: 13px 20px;
		background: var(--accent);
		color: #fff;
		font-weight: 700;
		font-size: .88rem;
		cursor: pointer;
		transition: var(--t);
		border: none;
		font-family: var(--font);
		white-space: nowrap;
		flex-shrink: 0
	}

	.nav-cats-btn:hover {
		background: var(--accent-dark)
	}

	.nav-cats-btn svg {
		width: 17px;
		height: 17px;
		stroke-width: 2.5
	}

	.nav-links {
		display: flex;
		align-items: stretch
	}

	.nav-link {
		display: flex;
		align-items: center;
		gap: 5px;
		padding: 13px 15px;
		color: rgba(255, 255, 255, .82);
		font-size: .86rem;
		font-weight: 500;
		transition: var(--t);
		white-space: nowrap;
		position: relative
	}

	.nav-link:hover {
		color: #fff;
		background: rgba(255, 255, 255, .07)
	}

	.nav-link.active {
		color: var(--accent)
	}

	.nav-link::after {
		content: '';
		position: absolute;
		bottom: 0;
		right: 0;
		left: 0;
		height: 2px;
		background: var(--accent);
		transform: scaleX(0);
		transition: var(--t)
	}

	.nav-link:hover::after,
	.nav-link.active::after {
		transform: scaleX(1)
	}

	.nav-link-hot {
		color: var(--accent) !important;
		font-weight: 700
	}

	/* Mega Menu */
	.mega-wrap {
		position: relative
	}

	.mega-menu {
		position: absolute;
		top: 100%;
		right: 0;
		width: 820px;
		background: #fff;
		box-shadow: var(--shadow-xl);
		border-radius: 0 0 var(--r) var(--r);
		display: none;
		z-index: 200;
		border-top: 3px solid var(--accent)
	}

	.mega-menu.open {
		display: flex
	}

	.mega-sidebar {
		width: 210px;
		background: var(--bg-soft);
		padding: 14px 0;
		border-left: 1px solid var(--border);
		flex-shrink: 0
	}

	.mega-sitem {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 12px 18px;
		font-size: .875rem;
		color: var(--text);
		cursor: pointer;
		transition: var(--t)
	}

	.mega-sitem:hover,
	.mega-sitem.active {
		color: var(--accent);
		background: #fff
	}

	.mega-content {
		flex: 1;
		padding: 24px
	}

	.mega-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 10px
	}

	.mega-content-title {
		font-size: .75rem;
		font-weight: 700;
		color: var(--muted);
		text-transform: uppercase;
		letter-spacing: .5px;
		margin: 0 0 14px;
		line-height: 1.35
	}

	.mega-view-all {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		margin-top: 14px;
		padding: 10px 14px;
		font-size: .82rem;
		font-weight: 800;
		color: var(--accent);
		text-decoration: none;
		border-radius: var(--r-sm);
		border: 1px solid rgba(234, 84, 26, .35);
		transition: var(--t)
	}

	.mega-view-all:hover {
		background: rgba(234, 84, 26, .08)
	}

	.mega-sitem-ico {
		font-size: 1.05rem;
		flex-shrink: 0;
		line-height: 1
	}

	.mega-item {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 11px 12px;
		border-radius: var(--r-sm);
		transition: var(--t);
		font-size: .855rem;
		color: var(--text)
	}

	.mega-item:hover {
		background: var(--bg-soft);
		color: var(--accent)
	}

	.mega-icon {
		width: 38px;
		height: 38px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.15rem;
		flex-shrink: 0
	}

	.mega-item.mega-item--product {
		flex-direction: column;
		align-items: stretch;
		text-align: center;
		gap: 8px;
		padding: 10px 8px;
		text-decoration: none;
		border: 1px solid transparent
	}

	.mega-item.mega-item--product:hover {
		border-color: var(--border);
		background: #fff
	}

	.mega-pthumb {
		width: 100%;
		aspect-ratio: 1;
		background: var(--bg-soft);
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden
	}

	.mega-pthumb img {
		width: 78%;
		height: 78%;
		object-fit: contain
	}

	.mega-pmeta {
		display: flex;
		flex-direction: column;
		gap: 4px;
		min-width: 0;
		text-align: center
	}

	.mega-pname {
		font-size: .78rem;
		font-weight: 700;
		line-height: 1.3;
		color: var(--text);
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden
	}

	.mega-pprice {
		font-size: .8rem;
		font-weight: 800;
		color: var(--accent)
	}

	/* ============================================================
   HERO
   ============================================================ */
	.hero {
		background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 55%, #3d3868 100%);
    overflow: hidden;
    position: relative;
	}

	.hero::before {
		content: '';
		position: absolute;
		top: -15%;
		right: -5%;
		width: 580px;
		height: 580px;
		border-radius: 50%;
		background: rgba(255, 255, 255, .025);
		pointer-events: none
	}

	.hero::after {
		content: '';
		position: absolute;
		bottom: -20%;
		left: 5%;
		width: 320px;
		height: 320px;
		border-radius: 50%;
		background: rgba(234, 84, 26, .07);
		pointer-events: none
	}

	.hero-inner {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 40px;
		align-items: center;
		padding: 68px 0 60px;
		position: relative;
		z-index: 1
	}

	/* Hero Content */
	.hero-content {
		color: #fff
	}

	.hero-badge {
		display: inline-flex;
		align-items: center;
		gap: 7px;
		background: rgba(234, 84, 26, .18);
		color: var(--accent);
		padding: 6px 15px;
		border-radius: var(--r-full);
		font-size: .83rem;
		font-weight: 700;
		border: 1px solid rgba(234, 84, 26, .3);
		margin-bottom: 20px
	}

	.hero-title {
		font-size: 2.85rem;
		font-weight: 900;
		line-height: 1.18;
		margin-bottom: 18px
	}

	.hero-title span {
		color: var(--accent)
	}

	.hero-desc {
		font-size: 1rem;
		color: rgba(255, 255, 255, .72);
		margin-bottom: 34px;
		line-height: 1.85;
		max-width: 480px
	}

	.hero-actions {
		display: flex;
		gap: 14px;
		flex-wrap: wrap
	}

	.hero-stats {
		display: flex;
		gap: 30px;
		margin-top: 42px;
		padding-top: 34px;
		border-top: 1px solid rgba(255, 255, 255, .13)
	}

	.h-stat-num {
		font-size: 1.55rem;
		font-weight: 800;
		color: var(--accent)
	}

	.h-stat-lbl {
		font-size: .78rem;
		color: rgba(255, 255, 255, .55);
		margin-top: 2px
	}

	.hero-dots {
		display: flex;
		gap: 8px;
		margin-top: 28px
	}

	.hero-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: rgba(255, 255, 255, .28);
		cursor: pointer;
		transition: var(--t);
		border: none
	}

	.hero-dot.active {
		width: 26px;
		border-radius: 4px;
		background: var(--accent)
	}

	/* Hero Visual */
	.hero-visual {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.hero-glow {
		position: absolute;
		width: 380px;
		height: 380px;
		border-radius: 50%;
		background: rgba(255, 255, 255, .04);
		border: 1px solid rgba(255, 255, 255, .08)
	}

	.hero-img {
		width: 100%;
		max-width: 430px;
		filter: drop-shadow(0 24px 64px rgba(0, 0, 0, .45));
		animation: float 3.5s ease-in-out infinite;
		position: relative;
		z-index: 1
	}

	@keyframes float {

		0%,
		100% {
			transform: translateY(0)
		}

		50% {
			transform: translateY(-14px)
		}
	}

	/* Floating badges */
	.float-badge {
		position: absolute;
		background: #fff;
		border-radius: var(--r);
		padding: 11px 15px;
		box-shadow: var(--shadow-lg);
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: .83rem;
		z-index: 2;
		animation: fbFloat 3s ease-in-out infinite
	}

	.fb1 {
		bottom: 14%;
		right: -18px;
		animation-delay: 0s
	}

	.fb2 {
		top: 12%;
		left: -18px;
		animation-delay: 1.6s
	}

	@keyframes fbFloat {

		0%,
		100% {
			transform: translateY(0) rotate(-1deg)
		}

		50% {
			transform: translateY(-9px) rotate(1deg)
		}
	}

	.fb-icon {
		width: 34px;
		height: 34px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1rem;
		flex-shrink: 0
	}

	.fb-strong {
		display: block;
		font-size: .88rem;
		font-weight: 700;
		color: var(--primary)
	}

	.fb-small {
		font-size: .72rem;
		color: var(--muted)
	}

	/* ============================================================
   CATEGORIES
   ============================================================ */
	.cats-section {
		background: var(--bg-soft)
	}

	.cats-grid {
		display: grid;
		grid-template-columns: repeat(6, 1fr);
		gap: 18px
	}

	.cat-card {
		background: #fff;
		border-radius: var(--r);
		padding: 22px 14px;
		text-align: center;
		transition: var(--t);
		cursor: pointer;
		border: 2px solid transparent;
		box-shadow: var(--shadow-sm)
	}

	.cat-card:hover {
		border-color: var(--accent);
		transform: translateY(-6px);
		box-shadow: var(--shadow-lg)
	}

	.cat-icon {
		width: 62px;
		height: 62px;
		border-radius: var(--r);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.75rem;
		margin: 0 auto 14px;
		transition: var(--t)
	}

	.cat-card:hover .cat-icon {
		transform: scale(1.1)
	}

	.cat-name {
		font-weight: 700;
		font-size: .875rem;
		color: var(--text);
		margin-bottom: 4px
	}

	.cat-count {
		font-size: .72rem;
		color: var(--muted)
	}

	/* ============================================================
   PRODUCTS GRID
   ============================================================ */
	.products-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 22px
	}

	.prod-card {
		background: #fff;
		border-radius: var(--r);
		overflow: hidden;
		box-shadow: var(--shadow-sm);
		border: 1px solid #f0f0f8;
		transition: var(--t);
		position: relative
	}

	.prod-card:hover {
		box-shadow: var(--shadow-lg);
		transform: translateY(-7px);
		border-color: transparent
	}

	.prod-img-wrap {
		position: relative;
		overflow: hidden;
		background: var(--bg-soft);
		aspect-ratio: 1;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.prod-img {
		width: 80%;
		aspect-ratio: 1;
		object-fit: contain;
		transition: transform .4s ease
	}

	.prod-card:hover .prod-img {
		transform: scale(1.07)
	}

	/* Badges */
	.prod-badges {
		position: absolute;
		top: 11px;
		right: 11px;
		display: flex;
		flex-direction: column;
		gap: 5px;
		z-index: 1
	}

	.badge-disc {
		background: var(--accent);
		color: #fff;
		font-size: .7rem;
		font-weight: 800;
		padding: 3px 9px;
		border-radius: var(--r-full)
	}

	.badge-new {
		background: var(--success);
		color: #fff;
		font-size: .7rem;
		font-weight: 800;
		padding: 3px 9px;
		border-radius: var(--r-full)
	}

	.badge-hot {
		background: var(--warning);
		color: #fff;
		font-size: .7rem;
		font-weight: 800;
		padding: 3px 9px;
		border-radius: var(--r-full)
	}

	/* Quick actions (slide up) */
	.prod-actions {
		position: absolute;
		bottom: -52px;
		right: 0;
		left: 0;
		padding: 10px 12px;
		background: #fff;
		display: flex;
		gap: 7px;
		transition: var(--t);
		z-index: 2
	}

	.prod-card:hover .prod-actions {
		bottom: 0
	}

	.pa-cart {
		flex: 1;
		padding: 9px 6px;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: var(--r-sm);
		font-size: .8rem;
		font-weight: 700;
		cursor: pointer;
		font-family: var(--font);
		transition: var(--t);
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 5px
	}

	.pa-cart:hover {
		background: var(--accent-dark)
	}

	.pa-icon {
		width: 34px;
		height: 34px;
		flex-shrink: 0;
		border: none;
		border-radius: var(--r-sm);
		background: var(--bg-soft);
		color: var(--muted);
		cursor: pointer;
		transition: var(--t);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: .95rem
	}

	.pa-icon:hover {
		background: var(--primary);
		color: #fff
	}

	.pa-wish:hover {
		background: #fee2e2;
		color: var(--danger)
	}

	/* Info */
	.prod-info {
		padding: 14px 16px
	}

	.prod-brand {
		font-size: .72rem;
		color: var(--muted);
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .4px;
		margin-bottom: 5px
	}

	.prod-name {
		font-size: .91rem;
		font-weight: 600;
		color: var(--text);
		margin-bottom: 9px;
		line-height: 1.4;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden
	}

	.prod-name a {
		color: inherit;
		text-decoration: none
	}

	.prod-name a:hover {
		color: var(--accent)
	}

	.stars-row {
		display: flex;
		align-items: center;
		gap: 5px;
		margin-bottom: 9px;
		font-size: .82rem
	}

	.stars {
		color: var(--warning)
	}

	.rev-count {
		color: var(--light);
		font-size: .75rem
	}

	.price-row {
		display: flex;
		align-items: center;
		gap: 9px;
		flex-wrap: wrap
	}

	.price-now {
		font-size: 1.12rem;
		font-weight: 800;
		color: var(--accent)
	}

	.price-was {
		font-size: .82rem;
		color: var(--light);
		text-decoration: line-through
	}

	select.prod-variant {
		width: 100%;
		padding: 7px 9px;
		border: 1px solid var(--border);
		border-radius: 8px;
		font-size: .78rem;
		font-family: var(--font);
		background: #fff;
		color: var(--text);
		font-weight: 600
	}

	/* ============================================================
   FLASH DEALS
   ============================================================ */
	.flash-section {
		background: var(--primary)
	}

	.flash-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 30px
	}

	.flash-title {
		font-size: 1.75rem;
		font-weight: 800;
		color: #fff;
		display: flex;
		align-items: center;
		gap: 11px
	}

	.flash-icon {
		font-size: 1.85rem;
		animation: pulse 1.6s ease-in-out infinite
	}

	@keyframes pulse {

		0%,
		100% {
			transform: scale(1)
		}

		50% {
			transform: scale(1.22)
		}
	}

	.timer {
		display: flex;
		align-items: center;
		gap: 7px
	}

	.t-block {
		background: rgba(255, 255, 255, .1);
		border: 1px solid rgba(255, 255, 255, .18);
		border-radius: var(--r-sm);
		padding: 7px 13px;
		text-align: center;
		min-width: 54px
	}

	.t-num {
		font-size: 1.35rem;
		font-weight: 800;
		color: #fff;
		display: block;
		line-height: 1
	}

	.t-lbl {
		font-size: .62rem;
		color: rgba(255, 255, 255, .55);
		margin-top: 2px;
		display: block
	}

	.t-sep {
		color: var(--accent);
		font-size: 1.4rem;
		font-weight: 800
	}

	.flash-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 18px
	}

	.flash-card {
		background: rgba(255, 255, 255, .06);
		border: 1px solid rgba(255, 255, 255, .11);
		border-radius: var(--r);
		padding: 20px;
		text-align: center;
		transition: var(--t);
		cursor: pointer
	}

	.flash-card:hover {
		background: rgba(255, 255, 255, .1);
		transform: translateY(-4px)
	}

	.flash-img {
		height: 130px;
		width: auto;
		object-fit: contain;
		margin: 0 auto 14px;
		display: block
	}

	.flash-name {
		color: #fff;
		font-size: .875rem;
		font-weight: 600;
		margin-bottom: 10px;
		line-height: 1.4;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden
	}

	.flash-prices {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 9px;
		margin-bottom: 12px
	}

	.flash-now {
		color: var(--accent);
		font-size: 1.15rem;
		font-weight: 800
	}

	.flash-was {
		color: rgba(255, 255, 255, .45);
		font-size: .82rem;
		text-decoration: line-through
	}

	.flash-bar-wrap {
		background: rgba(255, 255, 255, .13);
		height: 5px;
		border-radius: 3px;
		overflow: hidden;
		margin-bottom: 5px
	}

	.flash-bar {
		height: 100%;
		background: var(--accent);
		border-radius: 3px
	}

	.flash-sold {
		font-size: .7rem;
		color: rgba(255, 255, 255, .55)
	}

	.flash-btn {
		width: 100%;
		padding: 9px;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: var(--r-sm);
		font-weight: 700;
		font-size: .82rem;
		cursor: pointer;
		margin-top: 12px;
		transition: var(--t);
		font-family: var(--font)
	}

	.flash-btn:hover {
		background: var(--accent-dark)
	}

	/* ============================================================
   BRANDS
   ============================================================ */
	.brands-section {
		background: var(--bg-soft)
	}

	.brands-track {
		display: flex;
		gap: 16px;
		overflow-x: auto;
		padding-bottom: 4px;
		scrollbar-width: none
	}

	.brands-track::-webkit-scrollbar {
		display: none
	}

	.brand-tile {
		flex-shrink: 0;
		background: #fff;
		border: 1px solid var(--border);
		border-radius: var(--r);
		padding: 18px 30px;
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 140px;
		height: 76px;
		transition: var(--t);
		font-size: 1.05rem;
		font-weight: 800;
		color: var(--light)
	}

	.brand-tile:hover {
		border-color: var(--accent);
		color: var(--primary);
		transform: translateY(-3px);
		box-shadow: var(--shadow-md)
	}

	/* ============================================================
   OFFER BANNER
   ============================================================ */
	.offer-banner {
		background: linear-gradient(135deg, var(--accent) 0%, #ff7640 100%);
		border-radius: var(--r-lg);
		padding: 50px 52px;
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 36px;
		align-items: center;
		position: relative;
		overflow: hidden
	}

	.offer-banner::before {
		content: '';
		position: absolute;
		top: -55%;
		left: -15%;
		width: 420px;
		height: 420px;
		background: rgba(255, 255, 255, .055);
		border-radius: 50%;
		pointer-events: none
	}

	.offer-banner::after {
		content: '';
		position: absolute;
		bottom: -40%;
		right: -8%;
		width: 300px;
		height: 300px;
		background: rgba(255, 255, 255, .04);
		border-radius: 50%;
		pointer-events: none
	}

	.offer-content {
		color: #fff;
		position: relative;
		z-index: 1
	}

	.offer-tag {
		display: inline-block;
		background: rgba(255, 255, 255, .2);
		color: #fff;
		padding: 4px 14px;
		border-radius: var(--r-full);
		font-size: .78rem;
		font-weight: 800;
		margin-bottom: 16px;
		letter-spacing: .5px
	}

	.offer-title {
		font-size: 2.1rem;
		font-weight: 900;
		line-height: 1.2;
		margin-bottom: 12px
	}

	.offer-desc {
		font-size: .93rem;
		color: rgba(255, 255, 255, .84);
		margin-bottom: 28px;
		line-height: 1.75
	}

	.offer-btns {
		display: flex;
		gap: 12px;
		flex-wrap: wrap
	}

	.btn-dark {
		background: var(--primary);
		color: #fff;
		border-color: var(--primary)
	}

	.btn-dark:hover {
		background: var(--primary-light);
		transform: translateY(-2px);
		box-shadow: var(--shadow-md)
	}

	.btn-white-outline {
		background: transparent;
		color: #fff;
		border-color: rgba(255, 255, 255, .5)
	}

	.btn-white-outline:hover {
		background: #fff;
		color: var(--accent)
	}

	.offer-visual {
		display: flex;
		justify-content: center;
		align-items: center;
		position: relative;
		z-index: 1
	}

	.offer-visual img {
		max-height: 210px;
		filter: drop-shadow(0 20px 44px rgba(0, 0, 0, .3));
		animation: float 3.5s ease-in-out infinite 1s
	}

	/* ============================================================
   TESTIMONIALS
   ============================================================ */
	.testi-section {
		background: var(--bg-soft)
	}

	.testi-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 22px
	}

	.testi-card {
		background: #fff;
		border-radius: var(--r);
		padding: 28px;
		box-shadow: var(--shadow-sm);
		border: 1px solid #f0f0f8;
		transition: var(--t)
	}

	.testi-card:hover {
		box-shadow: var(--shadow-md);
		transform: translateY(-3px)
	}

	.testi-q {
		font-size: 2.4rem;
		color: var(--accent);
		opacity: .28;
		line-height: 1;
		margin-bottom: 8px
	}

	.testi-text {
		color: var(--muted);
		font-size: .875rem;
		line-height: 1.85;
		margin-bottom: 20px
	}

	.testi-author {
		display: flex;
		align-items: center;
		gap: 11px
	}

	.testi-av {
		width: 42px;
		height: 42px;
		border-radius: 50%;
		background: linear-gradient(135deg, var(--primary), var(--accent));
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-weight: 800;
		font-size: .95rem;
		flex-shrink: 0
	}

	.testi-name {
		font-weight: 700;
		font-size: .875rem;
		color: var(--text)
	}

	.testi-loc {
		font-size: .72rem;
		color: var(--muted);
		margin-top: 2px
	}

	/* ============================================================
   NEWSLETTER
   ============================================================ */
	.news-section {
		background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
		padding: 72px 0;
		text-align: center
	}

	.news-title {
		font-size: 1.95rem;
		font-weight: 800;
		color: #fff;
		margin-bottom: 10px
	}

	.news-sub {
		color: rgba(255, 255, 255, .65);
		margin-bottom: 34px;
		font-size: .95rem
	}

	.news-form {
		display: flex;
		max-width: 460px;
		margin: 0 auto;
		background: #fff;
		border-radius: var(--r-full);
		overflow: hidden;
		box-shadow: var(--shadow-xl)
	}

	.news-input {
		flex: 1;
		padding: 13px 22px;
		border: none;
		background: transparent;
		font-size: .93rem;
		font-family: var(--font);
		color: var(--text)
	}

	.news-input::placeholder {
		color: var(--light)
	}

	.news-btn {
		padding: 13px 26px;
		background: var(--accent);
		color: #fff;
		border: none;
		font-weight: 700;
		cursor: pointer;
		font-family: var(--font);
		font-size: .88rem;
		transition: var(--t);
		white-space: nowrap
	}

	.news-btn:hover {
		background: var(--accent-dark)
	}

	/* ============================================================
   FOOTER
   ============================================================ */
	.site-footer {
		background: var(--primary-dark);
		color: rgba(255, 255, 255, .75);
		padding: 60px 0 0
	}

	.footer-grid {
		display: grid;
		grid-template-columns: 2fr 1fr 1fr 1fr;
		gap: 44px;
		margin-bottom: 48px
	}

	.f-logo {
		display: flex;
		align-items: center;
		gap: 11px;
		margin-bottom: 16px
	}

	.f-desc {
		font-size: .855rem;
		line-height: 1.85;
		color: rgba(255, 255, 255, .55);
		margin-bottom: 20px
	}

	.f-social {
		display: flex;
		gap: 9px
	}

	.soc-btn {
		width: 34px;
		height: 34px;
		border-radius: 50%;
		background: rgba(255, 255, 255, .07);
		display: flex;
		align-items: center;
		justify-content: center;
		color: rgba(255, 255, 255, .55);
		font-size: .85rem;
		transition: var(--t);
		cursor: pointer;
		font-weight: 700
	}

	.soc-btn:hover {
		background: var(--accent);
		color: #fff
	}

	.f-col-title {
		font-size: .95rem;
		font-weight: 700;
		color: #fff;
		margin-bottom: 18px
	}

	.f-links {
		display: flex;
		flex-direction: column;
		gap: 9px
	}

	.f-link {
		font-size: .845rem;
		color: rgba(255, 255, 255, .55);
		transition: var(--t)
	}

	.f-link:hover {
		color: var(--accent);
		padding-right: 4px
	}

	.footer-bottom {
		border-top: 1px solid rgba(255, 255, 255, .07);
		padding: 20px 0;
		display: flex;
		align-items: center;
		justify-content: space-between
	}

	.f-copy {
		font-size: .78rem;
		color: rgba(255, 255, 255, .35)
	}

	.pay-icons {
		display: flex;
		gap: 7px
	}

	.pay-ic {
		background: rgba(255, 255, 255, .09);
		border-radius: 5px;
		padding: 4px 9px;
		font-size: .72rem;
		color: rgba(255, 255, 255, .55);
		font-weight: 700
	}

	/* ============================================================
   CART DRAWER
   ============================================================ */
	.cart-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, .52);
		z-index: 300;
		opacity: 0;
		visibility: hidden;
		pointer-events: none;
		transition: var(--t);
		backdrop-filter: blur(5px);
		width: 100vw;
		max-width: 100vw;
	}

	.cart-overlay.open {
		opacity: 1;
		visibility: visible;
		pointer-events: auto;
	}

	.cart-drawer {
		position: fixed;
		top: 0;
		left: 0;
		width: 390px;
		height: 100vh;
		background: #fff;
		z-index: 310;
		transform: translateX(-105%);
		visibility: hidden;
		pointer-events: none;
		transition: transform .35s cubic-bezier(.4, 0, .2, 1);
		display: flex;
		flex-direction: column;
		box-shadow: var(--shadow-xl);
		max-width: 100vw;
	}

	.cart-drawer.open {
		transform: translateX(0);
		visibility: visible;
		pointer-events: auto;
	}

	.cd-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 18px 22px;
		border-bottom: 1px solid var(--border)
	}

	.cd-title {
		font-size: 1.05rem;
		font-weight: 700;
		color: var(--primary);
		display: flex;
		align-items: center;
		gap: 8px
	}

	.cd-close {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: var(--bg-soft);
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--muted);
		font-size: 1rem;
		transition: var(--t)
	}

	.cd-close:hover {
		background: var(--danger);
		color: #fff
	}

	.cd-items {
		flex: 1;
		overflow-y: auto;
		padding: 18px 22px
	}

	.cart-item {
		display: flex;
		gap: 13px;
		padding: 14px 0;
		border-bottom: 1px solid #f5f5fa
	}

	.ci-img {
		width: 68px;
		height: 68px;
		border-radius: var(--r-sm);
		object-fit: contain;
		background: var(--bg-soft);
		padding: 6px;
		flex-shrink: 0
	}

	.ci-info {
		flex: 1;
		min-width: 0
	}

	.ci-name {
		font-size: .875rem;
		font-weight: 600;
		margin-bottom: 4px;
		line-height: 1.35
	}

	.ci-price {
		font-size: .92rem;
		font-weight: 800;
		color: var(--accent)
	}

	.ci-qty {
		display: flex;
		align-items: center;
		gap: 7px;
		margin-top: 7px
	}

	.qty-btn {
		width: 24px;
		height: 24px;
		border-radius: 50%;
		border: 1px solid var(--border);
		background: #fff;
		cursor: pointer;
		font-size: .95rem;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: var(--t);
		color: var(--text);
		font-family: var(--font)
	}

	.qty-btn:hover {
		border-color: var(--accent);
		color: var(--accent)
	}

	.qty-val {
		font-weight: 700;
		font-size: .875rem;
		min-width: 18px;
		text-align: center
	}

	.ci-remove {
		background: none;
		border: none;
		color: var(--light);
		cursor: pointer;
		font-size: .8rem;
		transition: var(--t);
		align-self: flex-start;
		padding: 3px
	}

	.ci-remove:hover {
		color: var(--danger)
	}

	.cd-footer {
		padding: 18px 22px;
		border-top: 1px solid var(--border)
	}

	.cd-total {
		display: flex;
		justify-content: space-between;
		font-size: 1.05rem;
		font-weight: 800;
		color: var(--primary);
		margin-bottom: 14px
	}

	.cd-checkout {
		width: 100%;
		padding: 13px;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: var(--r-sm);
		font-weight: 700;
		font-size: .95rem;
		cursor: pointer;
		font-family: var(--font);
		transition: var(--t);
		margin-bottom: 8px
	}

	.cd-checkout:hover {
		background: var(--accent-dark)
	}

	.cd-continue {
		width: 100%;
		padding: 11px;
		background: transparent;
		color: var(--muted);
		border: 1px solid var(--border);
		border-radius: var(--r-sm);
		font-weight: 600;
		font-size: .875rem;
		cursor: pointer;
		font-family: var(--font);
		transition: var(--t)
	}

	.cd-continue:hover {
		border-color: var(--primary);
		color: var(--primary)
	}

	/* ============================================================
   TOAST
   ============================================================ */
	.toast-container {
		position: fixed;
		bottom: 22px;
		left: 22px;
		z-index: 500;
		display: flex;
		flex-direction: column;
		gap: 9px
	}

	.toast {
		display: flex;
		align-items: center;
		gap: 11px;
		background: #fff;
		border-radius: var(--r);
		padding: 13px 17px;
		box-shadow: var(--shadow-xl);
		min-width: 270px;
		border-right: 4px solid var(--success);
		animation: toastIn .3s ease;
		font-size: .875rem;
		font-weight: 500
	}

	.toast.error {
		border-color: var(--danger)
	}

	.toast.warn {
		border-color: var(--warning)
	}

	@keyframes toastIn {
		from {
			transform: translateY(18px);
			opacity: 0
		}

		to {
			transform: translateY(0);
			opacity: 1
		}
	}

	/* ============================================================
   QUICK VIEW MODAL
   ============================================================ */
	.modal-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, .62);
		z-index: 400;
		opacity: 0;
		visibility: hidden;
		pointer-events: none;
		transition: var(--t);
		backdrop-filter: blur(7px);
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		width: 100vw;
		max-width: 100vw;
		overflow: hidden;
	}

	.modal-overlay.open {
		opacity: 1;
		visibility: visible;
		pointer-events: auto;
	}

	.modal-box {
		background: #fff;
		border-radius: var(--r-lg);
		max-width: 780px;
		width: 100%;
		max-height: 90vh;
		overflow-y: auto;
		transform: scale(.92);
		transition: transform .3s cubic-bezier(.4, 0, .2, 1)
	}

	.modal-overlay.open .modal-box {
		transform: scale(1)
	}

	.modal-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 18px 22px;
		border-bottom: 1px solid var(--border)
	}

	.modal-title {
		font-size: 1.05rem;
		font-weight: 700;
		color: var(--primary)
	}

	.modal-close {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: var(--bg-soft);
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--muted);
		transition: var(--t)
	}

	.modal-close:hover {
		background: var(--danger);
		color: #fff
	}

	.modal-body {
		padding: 24px;
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 24px
	}

	@media (max-width: 640px) {
		.modal-body {
			grid-template-columns: 1fr;
		}
	}

	/* ============================================================
   MOBILE BOTTOM NAV
   ============================================================ */
	.mob-nav {
		display: none;
		position: fixed;
		bottom: 0;
		right: 0;
		left: 0;
		background: #fff;
		border-top: 1px solid var(--border);
		z-index: 200;
		box-shadow: 0 -4px 20px rgba(0, 0, 0, .08)
	}

	.mob-nav-inner {
		display: flex
	}

	.mob-nav-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 3px;
		padding: 9px 4px 7px;
		color: var(--light);
		font-size: .64rem;
		font-weight: 700;
		cursor: pointer;
		transition: var(--t);
		background: none;
		border: none;
		font-family: var(--font);
		position: relative
	}

	.mob-nav-item:hover,
	.mob-nav-item.active {
		color: var(--accent)
	}

	.mob-nav-item svg {
		width: 21px;
		height: 21px
	}

	/* ============================================================
   MOBILE MENU (side panel)
   ============================================================ */
	.mob-menu {
		position: fixed;
		top: 0;
		right: 0;
		width: 290px;
		height: 100vh;
		background: #fff;
		z-index: 450;
		transform: translateX(110%);
		visibility: hidden;
		pointer-events: none;
		transition: transform .35s cubic-bezier(.4, 0, .2, 1);
		overflow-y: auto;
		overflow-x: hidden;
		box-shadow: var(--shadow-xl);
		max-width: 100vw;
	}

	.mob-menu.open {
		transform: translateX(0);
		visibility: visible;
		pointer-events: auto;
	}

	.mob-menu-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, .45);
		opacity: 0;
		visibility: hidden;
		transition: var(--t);
		z-index: 440;
	}

	.mob-menu-overlay.open {
		opacity: 1;
		visibility: visible;
	}

	.mm-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 18px 20px;
		background: var(--primary);
		color: #fff
	}

	.mm-close {
		background: rgba(255, 255, 255, .12);
		border: none;
		color: #fff;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1rem
	}

	.mm-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 13px 20px;
		border-bottom: 1px solid #f5f5fa;
		font-size: .9rem;
		font-weight: 500;
		cursor: pointer;
		transition: var(--t)
	}

	.mm-item:hover {
		background: var(--bg-soft);
		color: var(--accent)
	}

	/* ============================================================
   RESPONSIVE
   ============================================================ */
	@media(max-width:1024px) {
		.cats-grid {
			grid-template-columns: repeat(3, 1fr)
		}

		.products-grid {
			grid-template-columns: repeat(3, 1fr)
		}

		.testi-grid {
			grid-template-columns: repeat(2, 1fr)
		}

		.footer-grid {
			grid-template-columns: 1fr 1fr;
			gap: 30px
		}

		.flash-grid {
			grid-template-columns: repeat(2, 1fr)
		}

		.hero-title {
			font-size: 2.3rem
		}

		.offer-banner {
			grid-template-columns: 1fr
		}
	}

	@media(max-width:768px) {
		.container {
			padding: 0 12px;
		}

		.announce .container {
			flex-direction: column;
			gap: 5px;
			text-align: center
		}

		.header-inner {
			flex-wrap: wrap;
			gap: 12px
		}

		.mob-menu-toggle {
			display: inline-flex !important;
			align-items: center;
			justify-content: center;
		}

		.logo {
			flex: 1;
			min-width: 0;
		}

		.header-actions {
			width: 100%;
			justify-content: space-between;
			overflow-x: auto;
			padding-bottom: 4px;
			-ms-overflow-style: none;
			scrollbar-width: none;
		}

		.header-actions::-webkit-scrollbar {
			display: none;
		}

		.header-search {
			order: 3;
			width: 100%
		}

		.search-cat {
			display: none
		}

		.main-nav {
			display: none
		}

		.hero-inner {
			grid-template-columns: 1fr;
			text-align: center;
			padding: 44px 0 36px
		}

		.hero-visual {
			display: none
		}

		.hero-title {
			font-size: 1.9rem
		}

		.hero-actions,
		.hero-stats {
			justify-content: center
		}

		.cats-grid {
			grid-template-columns: repeat(2, 1fr)
		}

		.products-grid {
			grid-template-columns: repeat(2, 1fr)
		}

		.flash-grid {
			grid-template-columns: 1fr
		}

		.testi-grid {
			grid-template-columns: 1fr
		}

		.footer-grid {
			grid-template-columns: 1fr
		}

		.mob-nav {
			display: block
		}

		body {
			padding-bottom: 62px
		}

		.hero-badge,
		.hero-dots {
			justify-content: center;
			display: flex
		}

		.section,
		.news-section {
			padding: 44px 0
		}

		.sec-title {
			font-size: 1.6rem
		}

		.offer-banner {
			padding: 32px 24px
		}

		.offer-title {
			font-size: 1.65rem
		}
	}

	@media(max-width:480px) {
		.container {
			padding: 0 8px;
		}

		.cats-grid {
			grid-template-columns: repeat(3, 1fr)
		}

		/* Two products per row on small phones; scale down card copy so it fits */
		.products-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 10px 8px
		}

		.prod-info {
			padding: 8px 10px
		}

		.prod-brand {
			font-size: .62rem;
			margin-bottom: 3px;
			letter-spacing: .2px
		}

		.prod-name {
			font-size: .78rem;
			margin-bottom: 6px;
			line-height: 1.35
		}

		.stars-row {
			font-size: .65rem;
			margin-bottom: 6px;
			gap: 3px
		}

		.rev-count {
			font-size: .62rem
		}

		.price-now {
			font-size: .92rem
		}

		.price-row {
			gap: 4px
		}

		.prod-actions {
			padding: 6px 8px;
			gap: 4px
		}

		.pa-cart {
			font-size: clamp(.58rem, 2.6vw, .68rem);
			padding: 6px 4px;
			gap: 3px;
			min-width: 0
		}

		.pa-cart svg {
			width: 12px;
			height: 12px;
			flex-shrink: 0
		}

		.pa-icon {
			width: 28px;
			height: 28px;
			font-size: .8rem
		}

		.prod-badges {
			top: 6px;
			right: 6px;
			gap: 4px
		}

		.badge-new,
		.badge-disc,
		.badge-hot {
			font-size: .58rem;
			padding: 2px 6px
		}

		.prod-variant-wrap {
			margin-bottom: 6px
		}

		select.prod-variant {
			font-size: .64rem;
			padding: 5px 6px;
			border-radius: 6px
		}

		.cart-drawer {
			width: 100%
		}

		.h-action span {
			display: none
		}

		.mob-menu {
			width: min(85vw, 320px);
		}
	}
	</style>
</head>