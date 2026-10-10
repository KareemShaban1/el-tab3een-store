# Discounts / Flash / Promo — Implementation Notes

Migration applied: `2026_10_10_180000_extend_discounts_for_flash_and_promo.php`

## Main code added/changed

### New
- `app/Services/Pricing/DiscountResolverService.php`
- `app/Services/Pricing/PromoCodeService.php`
- `app/PromoCode.php`, `app/DiscountRedemption.php`
- `app/Http/Controllers/PromoCodeController.php`
- `resources/views/promo_code/*`
- `resources/views/discount/partials/extra_fields.blade.php`
- User guides: `DISCOUNTS_FLASH_PROMO_ERP_USER_GUIDE.md`, `DISCOUNTS_FLASH_PROMO_CUSTOMER_GUIDE.md`

### Updated
- `app/Discount.php` — relations + new fields casts
- `app/Utils/ProductUtil.php` — resolver + invoice max cap
- `app/Utils/TransactionUtil.php` — promo header fields + line snapshots
- `app/Http/Controllers/DiscountController.php` — extended CRUD
- `app/Http/Controllers/SellPosController.php` — promo validate + redemptions
- `app/Http/Controllers/Frontend/StorefrontController.php` — real flash deals + validate promo
- `app/Http/Controllers/Frontend/StoreCheckoutController.php` — line discounts + promo codes
- `resources/views/frontend/store/checkout.blade.php` — promo UI
- `public/js/pos.js` — apply promo from discount modal
- `routes/web.php`, `AdminSidebarMenu.php`, `lang/en|ar/lang_v1.php`

## Deploy checklist
1. `php artisan migrate`
2. Clear caches if needed: `php artisan optimize:clear`
3. Users with `discount.access` see **Discounts** and **Promo Codes** under Sell
