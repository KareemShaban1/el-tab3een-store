<?php

namespace App\Http\Middleware;

use App\Services\WebsiteVisitLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class LogWebsiteVisit
{
    public function __construct(private WebsiteVisitLogger $logger) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): mixed  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $visitLogId = null;
        $visitToken = null;

        try {
            if (Schema::hasTable('website_visit_logs')) {
                $log = $this->logger->log($request);
                if ($log) {
                    $visitLogId = $log->id;
                    $visitToken = $log->visit_token;
                    View::share('websiteVisitLogId', $visitLogId);
                    View::share('websiteVisitToken', $visitToken);
                }
            }
        } catch (\Throwable $e) {
            // Never break storefront rendering because of logging failures.
            report($e);
        }

        $response = $next($request);

        if ($visitToken && method_exists($response, 'headers')) {
            $response->headers->set('X-Visit-Token', $visitToken);
        }

        return $response;
    }
}
