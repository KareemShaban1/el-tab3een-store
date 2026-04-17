<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetStorefrontLocale
{
    /**
     * Use Arabic for storefront routes (validation messages, __() in controllers).
     */
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale('ar');

        return $next($request);
    }
}
