<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Store' }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; color: #1b1f2a; }
        .nav { background: #111827; color: #fff; padding: 12px 20px; display:flex; gap:16px; align-items:center; }
        .nav a { color: #fff; text-decoration: none; }
        .container { max-width: 1080px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
        .btn { display:inline-block; background:#2563eb; color:#fff; border:none; border-radius:6px; padding:8px 12px; text-decoration:none; cursor:pointer; }
        .btn.secondary { background:#374151; }
        .btn.danger { background:#b91c1c; }
        input, select, textarea { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; margin-top:4px; margin-bottom:12px; }
        label { display:block; font-size:14px; font-weight:600; }
        .muted { color:#6b7280; font-size:13px; }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .alert { padding:10px; border-radius:6px; margin-bottom:12px; }
        .alert.success { background:#dcfce7; color:#166534; }
        .alert.error { background:#fee2e2; color:#991b1b; }
        .top-actions { margin-left:auto; display:flex; gap:8px; align-items:center; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="{{ route('store.home') }}">Store Home</a>
        <a href="{{ route('store.products.index') }}">Products</a>
        @auth('customer')
            <a href="{{ route('store.account.orders') }}">My Orders</a>
            <a href="{{ route('store.account.profile') }}">My Profile</a>
            <div class="top-actions">
                <span class="muted">{{ auth('customer')->user()->name }}</span>
                <form method="POST" action="{{ route('store.auth.logout') }}">
                    @csrf
                    <button class="btn danger" type="submit">Logout</button>
                </form>
            </div>
        @else
            <div class="top-actions">
                <a class="btn secondary" href="{{ route('store.auth.login.form') }}">Login</a>
                <a class="btn" href="{{ route('store.auth.register.form') }}">Register</a>
            </div>
        @endauth
    </div>

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
</body>
</html>

