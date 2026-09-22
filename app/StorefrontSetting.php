<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StorefrontSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
        'meta' => 'array',
    ];

    public static function forBusiness(int $business_id): self
    {
        return static::firstOrCreate(
            ['business_id' => $business_id],
            [
                'whatsapp_enabled' => false,
                'whatsapp_number' => null,
                'whatsapp_message' => null,
                'meta' => static::defaultContent(),
            ]
        );
    }

    /**
     * Default storefront content keys (JSON meta).
     * Add new keys here later — no migration needed.
     *
     * @return array<string, mixed>
     */
    public static function defaultContent(): array
    {
        return [
            'announce_enabled' => true,
            'announce_text' => '🎉 خصم يصل لـ 50% على أحدث الهواتف الذكية!',
            'announce_link_text' => 'تسوق الآن ←',
            'announce_link_url' => '',
            'brand_name' => 'التابعين',
            'brand_name_highlight' => 'للإلكترونيات',
            'brand_name_en' => 'El Tab3een Electronics',
            'footer_desc' => 'وجهتك الأولى للإلكترونيات في مصر. نوفر أحدث الأجهزة بأفضل الأسعار مع ضمان رسمي وخدمة متميزة ما بعد البيع.',
            'support_phone' => (string) config('storefront.support_phone', '19900'),
            'support_email' => (string) config('storefront.support_email', 'info@eltab3een.com'),
            'support_address' => 'القاهرة، مصر',
            'support_hours' => 'السبت–الخميس: 9ص–10م',
            'support_badge' => 'ضمان أصالة المنتجات',
            'copyright_text' => '',
            'social' => [
                'facebook' => '',
                'linkedin' => '',
                'x' => '',
                'youtube' => '',
                'whatsapp' => '',
            ],
        ];
    }

    /**
     * Merged content: defaults + saved meta (unknown future keys preserved).
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        return array_replace_recursive(static::defaultContent(), $this->meta ?? []);
    }

    public function contentValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->content(), $key, $default);
    }

    /**
     * Merge form values into meta without dropping unknown keys.
     *
     * @param  array<string, mixed>  $values
     */
    public function mergeContent(array $values): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $this->meta = array_replace_recursive($meta, $values);
    }

    public function supportPhone(): string
    {
        $phone = trim((string) $this->contentValue('support_phone', ''));

        return $phone !== ''
            ? $phone
            : (string) config('storefront.support_phone', '19900');
    }

    public function supportEmail(): string
    {
        $email = trim((string) $this->contentValue('support_email', ''));

        return $email !== ''
            ? $email
            : (string) config('storefront.support_email', 'info@eltab3een.com');
    }

    public function announceLinkUrl(): string
    {
        $url = trim((string) $this->contentValue('announce_link_url', ''));
        if ($url !== '') {
            return $url;
        }

        return route('store.products.index');
    }

    public function copyrightText(): string
    {
        $custom = trim((string) $this->contentValue('copyright_text', ''));
        if ($custom !== '') {
            return $custom;
        }

        $brand = trim(
            (string) $this->contentValue('brand_name', 'التابعين')
            .' '
            .(string) $this->contentValue('brand_name_highlight', 'للإلكترونيات')
        );

        return '© '.date('Y').' '.$brand.'. جميع الحقوق محفوظة.';
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
