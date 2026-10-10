<?php

namespace App\Http\Controllers;

use App\StorefrontSetting;
use App\Utils\Util;
use Illuminate\Http\Request;

class StorefrontWhatsAppController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin(auth()->user(), $business_id);

        if (! $is_admin && ! auth()->user()->can('storefront_whatsapp.access')) {
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

        return view('storefront_whatsapp.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_number' => 'nullable|string|max:32',
            'whatsapp_message' => 'nullable|string|max:1000',
        ]);

        $business_id = (int) $request->session()->get('user.business_id');
        $settings = StorefrontSetting::forBusiness($business_id);

        $settings->whatsapp_enabled = $request->boolean('whatsapp_enabled');
        $settings->whatsapp_number = trim((string) ($validated['whatsapp_number'] ?? ''));
        $settings->whatsapp_message = trim((string) ($validated['whatsapp_message'] ?? ''));
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
