<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
    ];

    public static function forBusiness(int $business_id): self
    {
        return static::firstOrCreate(
            ['business_id' => $business_id],
            [
                'whatsapp_enabled' => false,
                'whatsapp_number' => null,
                'whatsapp_message' => null,
            ]
        );
    }

    /**
     * Digits-only phone for wa.me links (country code, no + or spaces).
     */
    public function whatsappDigits(): string
    {
        return preg_replace('/\D+/', '', (string) $this->whatsapp_number) ?: '';
    }

    public function whatsappUrl(): ?string
    {
        $digits = $this->whatsappDigits();
        if ($digits === '') {
            return null;
        }

        $url = rtrim((string) config('constants.whatsapp_base_url', 'https://wa.me'), '/').'/'.$digits;
        $message = trim((string) $this->whatsapp_message);
        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    public function shouldShowWhatsappButton(): bool
    {
        return $this->whatsapp_enabled && $this->whatsappDigits() !== '';
    }
}
