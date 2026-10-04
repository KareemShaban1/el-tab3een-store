<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\EgyptGovernorateNormalizer;
use App\WebsiteVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebsiteVisitTrackingController extends Controller
{
    public function update(Request $request, string $token)
    {
        $log = WebsiteVisitLog::query()
            ->where('visit_token', $token)
            ->first();

        if (! $log) {
            return response()->json(['success' => false], 404);
        }

        // Only allow updates for a short window after the visit started.
        if ($log->created_at && $log->created_at->lt(now()->subDay())) {
            return response()->json(['success' => false], 410);
        }

        $validated = $request->validate([
            'time_spent_seconds' => 'nullable|integer|min:0|max:86400',
            'page_title' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',
            'events' => 'nullable|array|max:50',
            'events.*.type' => 'required_with:events|string|max:80',
            'events.*.label' => 'nullable|string|max:255',
            'events.*.at' => 'nullable|string|max:40',
            'events.*.meta' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_accuracy' => 'nullable|numeric|min:0|max:100000',
        ]);

        if (array_key_exists('time_spent_seconds', $validated) && $validated['time_spent_seconds'] !== null) {
            $incoming = (int) $validated['time_spent_seconds'];
            if ($incoming > (int) $log->time_spent_seconds) {
                $log->time_spent_seconds = $incoming;
            }
        }

        if (! empty($validated['page_title'])) {
            $log->page_title = Str::limit($validated['page_title'], 255, '');
        }

        if (! empty($validated['product_name']) && empty($log->product_name)) {
            $log->product_name = Str::limit($validated['product_name'], 255, '');
        }

        if (! empty($validated['events']) && is_array($validated['events'])) {
            $existing = is_array($log->events) ? $log->events : [];
            $merged = array_slice(array_merge($existing, $this->sanitizeEvents($validated['events'])), 0, 100);
            $log->events = $merged;
            $log->events_count = count($merged);
        }

        if (isset($validated['latitude'], $validated['longitude'])) {
            $accuracy = isset($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
            // Ignore very inaccurate browser positions.
            if ($accuracy === null || $accuracy <= 50000) {
                $geo = app(EgyptGovernorateNormalizer::class)->fromCoordinates(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
                $log->fill($geo);
            }
        }

        $log->last_activity_at = now();
        $log->save();

        return response()->json(['success' => true]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeEvents(array $events): array
    {
        $clean = [];
        foreach ($events as $event) {
            if (! is_array($event) || empty($event['type'])) {
                continue;
            }

            $clean[] = [
                'type' => Str::limit((string) $event['type'], 80, ''),
                'label' => isset($event['label']) ? Str::limit((string) $event['label'], 255, '') : null,
                'at' => isset($event['at']) ? Str::limit((string) $event['at'], 40, '') : now()->toIso8601String(),
                'meta' => isset($event['meta']) && is_array($event['meta'])
                    ? array_slice($event['meta'], 0, 10)
                    : null,
            ];
        }

        return $clean;
    }
}
