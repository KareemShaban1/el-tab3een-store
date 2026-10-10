<?php

namespace App\Http\Controllers;

use App\StorefrontSetting;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StorefrontAppearanceController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin(auth()->user(), $business_id);

        if (! $is_admin && ! auth()->user()->can('storefront_appearance.access')) {
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
        $settings = StorefrontSetting::forBusiness($business_id);
        $content = $settings->content();

        return view('storefront_appearance.edit', compact('settings', 'content'));
    }

    public function update(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'announce_enabled' => 'nullable|boolean',
            'announce_text' => 'nullable|string|max:500',
            'announce_link_text' => 'nullable|string|max:120',
            'announce_link_url' => 'nullable|string|max:500',
            'brand_name' => 'nullable|string|max:120',
            'brand_name_highlight' => 'nullable|string|max:120',
            'brand_name_en' => 'nullable|string|max:120',
            'footer_desc' => 'nullable|string|max:2000',
            'support_phone' => 'nullable|string|max:40',
            'support_email' => 'nullable|email|max:120',
            'support_address' => 'nullable|string|max:255',
            'support_hours' => 'nullable|string|max:255',
            'support_badge' => 'nullable|string|max:255',
            'copyright_text' => 'nullable|string|max:255',
            'social' => 'nullable|array',
            'social.facebook' => 'nullable|string|max:500',
            'social.linkedin' => 'nullable|string|max:500',
            'social.x' => 'nullable|string|max:500',
            'social.youtube' => 'nullable|string|max:500',
            'social.whatsapp' => 'nullable|string|max:500',
        ]);

        $business_id = (int) $request->session()->get('user.business_id');
        $settings = StorefrontSetting::forBusiness($business_id);

        $social = [];
        foreach (['facebook', 'linkedin', 'x', 'youtube', 'whatsapp'] as $network) {
            $social[$network] = trim((string) Arr::get($validated, "social.{$network}", ''));
        }

        $settings->mergeContent([
            'announce_enabled' => $request->boolean('announce_enabled'),
            'announce_text' => trim((string) ($validated['announce_text'] ?? '')),
            'announce_link_text' => trim((string) ($validated['announce_link_text'] ?? '')),
            'announce_link_url' => trim((string) ($validated['announce_link_url'] ?? '')),
            'brand_name' => trim((string) ($validated['brand_name'] ?? '')),
            'brand_name_highlight' => trim((string) ($validated['brand_name_highlight'] ?? '')),
            'brand_name_en' => trim((string) ($validated['brand_name_en'] ?? '')),
            'footer_desc' => trim((string) ($validated['footer_desc'] ?? '')),
            'support_phone' => trim((string) ($validated['support_phone'] ?? '')),
            'support_email' => trim((string) ($validated['support_email'] ?? '')),
            'support_address' => trim((string) ($validated['support_address'] ?? '')),
            'support_hours' => trim((string) ($validated['support_hours'] ?? '')),
            'support_badge' => trim((string) ($validated['support_badge'] ?? '')),
            'copyright_text' => trim((string) ($validated['copyright_text'] ?? '')),
            'social' => $social,
        ]);
        $settings->save();

        $output = [
            'success' => 1,
            'msg' => __('lang_v1.updated_success'),
        ];

        return redirect()
            ->action([self::class, 'edit'])
            ->with('status', $output);
    }
}
