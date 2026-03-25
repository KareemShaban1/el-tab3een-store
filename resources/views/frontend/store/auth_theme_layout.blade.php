<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'El Tab3een Store' }}</title>
    <style>
        :root {
            --primary: #2D294E;
            --primary-light: #3d3868;
            --accent: #EA541A;
            --accent-dark: #c43e0e;
            --bg: #FFFFFF;
            --bg-soft: #F8F9FC;
            --text: #1a1a2e;
            --muted: #6b7280;
            --border: #e5e7eb;
            --danger: #ef4444;
            --success: #10b981;
            --shadow-md: 0 4px 20px rgba(45, 41, 78, .10);
            --r: 12px;
            --r-sm: 8px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: var(--bg-soft);
            color: var(--text);
        }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .announce {
            background: var(--primary);
            color: rgba(255,255,255,.9);
            padding: 10px 0;
            font-size: 13px;
        }
        .announce .container { display: flex; justify-content: space-between; gap: 10px; }
        .site-header {
            background: #fff;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 0;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            font-weight: 800;
        }
        .logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--accent);
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 18px;
        }
        .nav-actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--r-sm);
            border: 0;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-dark); }
        .btn-secondary { background: var(--primary-light); color: #fff; }
        .btn-ghost { background: #eef2ff; color: var(--primary); }
        .page-wrap { padding: 36px 0; }
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
    </style>
</head>
<body>
    <div class="announce">
        <div class="container">
            <span>🚚 شحن سريع لجميع المحافظات</span>
            <span>🔒 مدفوعات آمنة 100%</span>
        </div>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('store.home') }}" class="logo">
                <span class="logo-icon">⚡</span>
                <span>التابعين للإلكترونيات</span>
            </a>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="{{ route('store.products.index') }}">المتجر</a>
                <a class="btn btn-secondary" href="{{ route('store.auth.login.form') }}">دخول</a>
                <a class="btn btn-primary" href="{{ route('store.auth.register.form') }}">تسجيل</a>
            </div>
        </div>
    </header>

    <main class="page-wrap">
        <div class="container">
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
        </div>
    </main>
</body>
</html>

