<?php

namespace App\Http\Controllers;

use App\StorefrontSeoPage;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontSeoController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin(auth()->user(), $business_id);

        if (! $is_admin && ! auth()->user()->can('storefront_seo.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (! is_storefront_business($business_id)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function edit()
    {
        $this->authorizeAccess();

        $business_id = (int) request()->session()->get('user.business_id');
        $definitions = StorefrontSeoPage::pageDefinitions();

        $existing = StorefrontSeoPage::query()
            ->where('business_id', $business_id)
            ->whereIn('page_key', array_keys($definitions))
            ->get()
            ->keyBy('page_key');

        $pages = [];
        foreach ($definitions as $key => $definition) {
            $row = $existing->get($key);
            $pages[$key] = [
                'label_key' => $definition['label_key'],
                'route_hint' => $definition['route_hint'] ?? '',
                'meta_title' => $row->meta_title ?? '',
                'meta_description' => $row->meta_description ?? '',
                'meta_keywords' => $row->meta_keywords ?? '',
                'og_image' => $row->og_image ?? '',
                'robots' => $row->robots ?? '',
            ];
        }

        return view('storefront_seo.edit', compact('pages'));
    }

    public function update(Request $request)
    {
        $this->authorizeAccess();

        $definitions = StorefrontSeoPage::pageDefinitions();
        $pageKeys = array_keys($definitions);

        $validated = $request->validate([
            'pages' => 'nullable|array',
            'pages.*.meta_title' => 'nullable|string|max:191',
            'pages.*.meta_description' => 'nullable|string|max:500',
            'pages.*.meta_keywords' => 'nullable|string|max:255',
            'pages.*.og_image' => 'nullable|string|max:500',
            'pages.*.robots' => 'nullable|string|max:64',
        ]);

        $business_id = (int) $request->session()->get('user.business_id');
        $inputPages = $validated['pages'] ?? [];

        DB::beginTransaction();
        try {
            foreach ($pageKeys as $pageKey) {
                $data = $inputPages[$pageKey] ?? [];

                $payload = [
                    'meta_title' => $this->nullableTrim($data['meta_title'] ?? null),
                    'meta_description' => $this->nullableTrim($data['meta_description'] ?? null),
                    'meta_keywords' => $this->nullableTrim($data['meta_keywords'] ?? null),
                    'og_image' => $this->nullableTrim($data['og_image'] ?? null),
                    'robots' => $this->nullableTrim($data['robots'] ?? null),
                ];

                $isEmpty = collect($payload)->every(fn ($v) => $v === null);

                if ($isEmpty) {
                    StorefrontSeoPage::query()
                        ->where('business_id', $business_id)
                        ->where('page_key', $pageKey)
                        ->delete();
                    continue;
                }

                StorefrontSeoPage::query()->updateOrCreate(
                    [
                        'business_id' => $business_id,
                        'page_key' => $pageKey,
                    ],
                    $payload
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->action([self::class, 'edit'])
            ->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ]);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
