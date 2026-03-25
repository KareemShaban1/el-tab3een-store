<?php

namespace App\Http\Middleware;

use Closure;

class EnsureStoreCustomerAuthenticated
{
    public function handle($request, Closure $next)
    {
        if (! auth('customer')->check()) {
            return redirect()->route('store.auth.login.form');
        }

        return $next($request);
    }
}

