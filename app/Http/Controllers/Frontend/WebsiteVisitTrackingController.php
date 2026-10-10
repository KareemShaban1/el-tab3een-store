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
            'browser_city' => 'nullable|string|max:120',
            'browser_region' => 'nullable|string|max:120',
            'browser_country' => 'nullable|string|max:120',
            'browser_country_code' => 'nullable|string|max:8',
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

        $hasBrowserLocation = isset($validated['latitude'], $validated['longitude'])
            || ! empty($validated['browser_city'])
            || ! empty($validated['browser_region']);

        if ($hasBrowserLocation) {
            $accuracy = isset($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
            if ($accuracy === null || $accuracy <= 50000) {
                $normalizer = app(EgyptGovernorateNormalizer::class);
                if (isset($validated['latitude'], $validated['longitude'])) {
                    $geo = $normalizer->fromCoordinates(
                        (float) $validated['latitude'],
                        (float) $validated['longitude']
                    );
                } else {
                    $geo = $normalizer->normalize([
                        'country' => $validated['browser_country'] ?? null,
                        'country_code' => $validated['browser_country_code'] ?? null,
                        'region' => $validated['browser_region'] ?? null,
                        'city' => $validated['browser_city'] ?? null,
                        'latitude' => null,
                        'longitude' => null,
                        'location_label' => null,
                    ], false);
                }

                // Prefer reverse-geocoded city/region names when available.
                if (! empty($validated['browser_city']) || ! empty($validated['browser_region'])) {
                    $named = $normalizer->normalize([
                        'country' => $validated['browser_country'] ?? ($geo['country'] ?? 'مصر'),
                        'country_code' => $validated['browser_country_code'] ?? ($geo['country_code'] ?? 'EG'),
                        'region' => $validated['browser_region'] ?? ($geo['region'] ?? null),
                        'city' => $validated['browser_city'] ?? ($geo['city'] ?? null),
                        'latitude' => $geo['latitude'] ?? ($validated['latitude'] ?? null),
                        'longitude' => $geo['longitude'] ?? ($validated['longitude'] ?? null),
                        'location_label' => null,
                    ], ! empty($validated['latitude']));
                    if (! empty($named['location_label'])) {
                        $geo = $named;
                    }
                }

                $geo['location_source'] = 'browser';
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
