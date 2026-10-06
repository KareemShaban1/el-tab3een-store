<?php

namespace App\Http\Controllers;

use App\Services\WebsiteVisitLogger;
use App\System;
use App\Utils\Util;
use App\WebsiteVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WebsiteVisitLogController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin(auth()->user(), $business_id);

        if (! $is_admin && ! auth()->user()->can('website_logs.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $logs = WebsiteVisitLog::query()
                ->forBusiness($business_id)
                ->select([
                    'website_visit_logs.id',
                    'website_visit_logs.created_at',
                    'website_visit_logs.ip_address',
                    'website_visit_logs.country',
                    'website_visit_logs.country_code',
                    'website_visit_logs.region',
                    'website_visit_logs.city',
                    'website_visit_logs.location_label',
                    'website_visit_logs.page_path',
                    'website_visit_logs.page_title',
                    'website_visit_logs.page_type',
                    'website_visit_logs.product_id',
                    'website_visit_logs.product_name',
                    'website_visit_logs.product_source',
                    'website_visit_logs.time_spent_seconds',
                    'website_visit_logs.events',
                    'website_visit_logs.events_count',
                    'website_visit_logs.referer',
                    'website_visit_logs.is_bot',
                    'website_visit_logs.bot_name',
                    'website_visit_logs.user_agent',
                ]);

            $this->applyFilters($logs, request());

            return DataTables::of($logs)
                ->editColumn('created_at', function ($row) {
                    if (empty($row->created_at)) {
                        return '-';
                    }

                    // Use raw DB wall-clock + business formatter (same as rest of admin)
                    // to avoid UTC → Cairo double conversion (+2 hours).
                    $raw = $row->getRawOriginal('created_at') ?? $row->created_at;

                    return $this->util->format_date($raw, true) ?: '-';
                })
                ->addColumn('visitor_info', function ($row) {
                    $ip = e($row->ip_address ?: '-');
                    $location = $row->locationDisplay();
                    if ($location === '') {
                        $locationHtml = '<span class="text-muted">'.__('website_logs.location_unknown').'</span>';
                    } else {
                        $locationHtml = '<span title="'.e($location).'"><i class="fa fa-map-marker"></i> '.e(\Illuminate\Support\Str::limit($location, 40)).'</span>';
                    }

                    return '<div class="wl-cell"><div class="wl-ip">'.$ip.'</div><div class="wl-muted">'.$locationHtml.'</div></div>';
                })
                ->editColumn('page_path', function ($row) {
                    $types = WebsiteVisitLog::pageTypes();
                    $typeLabel = $types[$row->page_type] ?? ($row->page_type ?: '-');
                    $path = $row->page_path ?: '-';
                    $title = $row->page_title ?: $path;

                    return '<div class="wl-cell">'
                        .'<div class="wl-strong" title="'.e($title).'">'.e(\Illuminate\Support\Str::limit($title, 42)).'</div>'
                        .'<div class="wl-muted" title="'.e($path).'">'.e(\Illuminate\Support\Str::limit($path, 36)).'</div>'
                        .'<span class="label label-default">'.e($typeLabel).'</span>'
                        .'</div>';
                })
                ->editColumn('time_spent_seconds', function ($row) {
                    return $this->formatDuration((int) $row->time_spent_seconds);
                })
                ->addColumn('visitor_type', function ($row) {
                    if ($row->is_bot) {
                        $name = $row->bot_name ? ' '.e(\Illuminate\Support\Str::limit($row->bot_name, 18)) : '';

                        return '<span class="label label-warning">'.__('website_logs.bot').$name.'</span>';
                    }

                    return '<span class="label label-success">'.__('website_logs.human').'</span>';
                })
                ->addColumn('events_preview', function ($row) {
                    $events = is_array($row->events) ? $row->events : [];
                    if ($events === []) {
                        return '<span class="text-muted">-</span>';
                    }

                    $preview = collect($events)
                        ->take(3)
                        ->map(fn ($event) => WebsiteVisitLog::formatEventHtml($event))
                        ->filter()
                        ->implode('');

                    $more = count($events) > 3
                        ? '<small class="text-muted">+'.(count($events) - 3).' '.__('website_logs.more_events').'</small>'
                        : '';

                    return '<div class="wl-events">'.$preview.$more.'</div>';
                })
                ->editColumn('referer', function ($row) {
                    if (empty($row->referer)) {
                        return '<span class="text-muted">-</span>';
                    }

                    $host = parse_url($row->referer, PHP_URL_HOST) ?: $row->referer;
                    $short = \Illuminate\Support\Str::limit((string) $host, 28);

                    return '<a class="wl-referer" href="'.e($row->referer).'" target="_blank" rel="noopener noreferrer" title="'.e($row->referer).'">'.e($short).'</a>';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" data-href="'.action([self::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_website_log_button" title="'.__('messages.delete').'"><i class="glyphicon glyphicon-trash"></i></button>';
                })
                ->rawColumns(['visitor_info', 'page_path', 'visitor_type', 'events_preview', 'referer', 'action'])
                ->make(true);
        }

        // Backfill older rows missing city/location without blocking the page render.
        dispatch(function () {
            try {
                app(\App\Services\IpGeolocationService::class)->backfillMissing(40, false);
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();

        $stats = $this->buildStats($business_id, request());

        return view('website_logs.index', [
            'stats' => $stats,
            'pageTypes' => WebsiteVisitLog::pageTypes(),
            'visitorDisplayMode' => WebsiteVisitLogger::visitorDisplayMode(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'display_mode' => 'required|in:human,bot,both',
        ]);

        System::addProperty('website_logs_display_mode', $validated['display_mode']);

        return response()->json([
            'success' => true,
            'msg' => __('website_logs.settings_saved'),
            'display_mode' => $validated['display_mode'],
        ]);
    }

    public function stats(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) $request->session()->get('user.business_id');

        return response()->json([
            'success' => true,
            'stats' => $this->buildStats($business_id, $request),
        ]);
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $log = WebsiteVisitLog::forBusiness($business_id)->findOrFail($id);
        $log->delete();

        return [
            'success' => true,
            'msg' => __('lang_v1.success'),
        ];
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) $request->session()->get('user.business_id');

        $query = WebsiteVisitLog::query()->forBusiness($business_id);
        $this->applyFilters($query, $request);

        if ($request->filled('ids') && is_array($request->input('ids'))) {
            $ids = array_filter(array_map('intval', $request->input('ids')));
            $query->whereIn('id', $ids);
        }

        $deleted = $query->delete();

        return [
            'success' => true,
            'msg' => __('website_logs.deleted_count', ['count' => $deleted]),
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('start_date')) {
            $query->whereDate('website_visit_logs.created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('website_visit_logs.created_at', '<=', $request->input('end_date'));
        }

        if ($request->filled('ip_address')) {
            $query->where('website_visit_logs.ip_address', 'like', '%'.$request->input('ip_address').'%');
        }

        if ($request->filled('location')) {
            $location = $request->input('location');
            $query->where(function ($q) use ($location) {
                $q->where('website_visit_logs.location_label', 'like', '%'.$location.'%')
                    ->orWhere('website_visit_logs.city', 'like', '%'.$location.'%')
                    ->orWhere('website_visit_logs.region', 'like', '%'.$location.'%')
                    ->orWhere('website_visit_logs.country', 'like', '%'.$location.'%')
                    ->orWhere('website_visit_logs.country_code', 'like', '%'.$location.'%');
            });
        }

        if ($request->filled('page_filter')) {
            $page = $request->input('page_filter');
            $query->where(function ($q) use ($page) {
                $q->where('website_visit_logs.page_path', 'like', '%'.$page.'%')
                    ->orWhere('website_visit_logs.page_title', 'like', '%'.$page.'%')
                    ->orWhere('website_visit_logs.page_url', 'like', '%'.$page.'%');
            });
        }

        if ($request->filled('page_type')) {
            $query->where('website_visit_logs.page_type', $request->input('page_type'));
        }

        // Display settings control what appears in the table (source of truth).
        $displayMode = WebsiteVisitLogger::visitorDisplayMode();
        if ($displayMode === 'bot') {
            $query->where('website_visit_logs.is_bot', true);
        } elseif ($displayMode === 'human') {
            $query->where('website_visit_logs.is_bot', false);
        }

        if ($request->filled('referer')) {
            $query->where('website_visit_logs.referer', 'like', '%'.$request->input('referer').'%');
        }

        if ($request->filled('product')) {
            $product = $request->input('product');
            $query->where(function ($q) use ($product) {
                $q->where('website_visit_logs.product_name', 'like', '%'.$product.'%');
                if (is_numeric($product)) {
                    $q->orWhere('website_visit_logs.product_id', (int) $product);
                }
            });
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(int $businessId, Request $request): array
    {
        $base = WebsiteVisitLog::query()->forBusiness($businessId);
        $this->applyFilters($base, $request);

        $total = (clone $base)->count();
        $humans = (clone $base)->where('is_bot', false)->count();
        $bots = (clone $base)->where('is_bot', true)->count();
        $avgTime = (int) round((clone $base)->where('is_bot', false)->avg('time_spent_seconds') ?? 0);

        $topPages = (clone $base)
            ->select('page_path', 'page_type', DB::raw('COUNT(*) as visits'))
            ->whereNotNull('page_path')
            ->groupBy('page_path', 'page_type')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $topProducts = (clone $base)
            ->select('product_id', 'product_name', 'product_source', DB::raw('COUNT(*) as visits'))
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'product_name', 'product_source')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $topReferers = (clone $base)
            ->select('referer', DB::raw('COUNT(*) as visits'))
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->groupBy('referer')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $byType = (clone $base)
            ->select('page_type', DB::raw('COUNT(*) as visits'))
            ->groupBy('page_type')
            ->orderByDesc('visits')
            ->get();

        return [
            'total' => $total,
            'humans' => $humans,
            'bots' => $bots,
            'avg_time_spent' => $this->formatDuration($avgTime),
            'avg_time_spent_seconds' => $avgTime,
            'top_pages' => $topPages,
            'top_products' => $topProducts,
            'top_referers' => $topReferers,
            'by_type' => $byType,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%dh %dm %ds', $h, $m, $s);
        }

        if ($m > 0) {
            return sprintf('%dm %ds', $m, $s);
        }

        return sprintf('%ds', $s);
    }
}
