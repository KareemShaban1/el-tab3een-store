<?php

namespace App\Services\Pricing;

use App\Discount;
use App\DiscountRedemption;
use App\Product;
use App\PromoCode;
use App\Transaction;
use App\Variation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    protected $resolver;

    public function __construct(DiscountResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Validate a promo code against cart context.
     *
     * @return array{success: bool, msg?: string, promo_code?: PromoCode, discount?: Discount, application?: string, invoice_discount?: array, line_updates?: array, savings?: float}
     */
    public function validate(string $code, array $context): array
    {
        $business_id = $context['business_id'] ?? null;
        $normalized = PromoCode::normalizeCode($code);

        if (empty($business_id) || $normalized === '') {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_invalid')];
        }

        $promo = PromoCode::where('business_id', $business_id)
            ->where('code', $normalized)
            ->where('is_active', 1)
            ->with('discount.customerGroups', 'discount.variations')
            ->first();

        if (empty($promo) || empty($promo->discount)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_invalid')];
        }

        $discount = $promo->discount;

        if (empty($discount->is_active) || empty($discount->requires_promo_code)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_inactive')];
        }

        $channel = $context['channel'] ?? 'ecommerce';
        if ($channel === 'ecommerce' && empty($discount->apply_in_ecommerce)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_not_for_channel')];
        }
        if ($channel === 'pos' && empty($discount->apply_in_pos)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_not_for_channel')];
        }

        $location_id = $context['location_id'] ?? null;
        if (! empty($discount->location_id) && ! empty($location_id) && (int) $discount->location_id !== (int) $location_id) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_wrong_location')];
        }

        $now = Carbon::now();
        if (! $this->isWithinWindow($promo, $discount, $now)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_expired')];
        }

        if (! $this->passesCustomerGroup($discount, $context['customer_group_id'] ?? null)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_customer_group')];
        }

        $max_total = $promo->is_single_use ? 1 : $promo->max_uses_total;
        if (! is_null($max_total) && (int) $promo->used_count >= (int) $max_total) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_exhausted')];
        }

        $contact_id = $context['contact_id'] ?? null;
        if (! is_null($promo->max_uses_per_customer)) {
            if (empty($contact_id)) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_login_required')];
            }
            $used = DiscountRedemption::where('promo_code_id', $promo->id)
                ->where('contact_id', $contact_id)
                ->count();
            if ($used >= (int) $promo->max_uses_per_customer) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_customer_limit')];
            }
        }

        $cart_subtotal = (float) ($context['cart_subtotal'] ?? 0);
        if (! empty($discount->min_cart_subtotal) && $cart_subtotal + 0.0001 < (float) $discount->min_cart_subtotal) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_min_cart', ['amount' => $discount->min_cart_subtotal])];
        }

        if (! empty($discount->first_order_only)) {
            if (empty($contact_id)) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_login_required')];
            }
            $prior = Transaction::where('contact_id', $contact_id)
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->exists();
            if ($prior) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_first_order_only')];
            }
        }

        $application = $discount->promo_application ?: 'eligible_lines';
        $cart_lines = $context['cart_lines'] ?? [];

        if ($application === 'cart_wide') {
            $savings = $this->computeCartWideSavings($cart_subtotal, $discount);

            return [
                'success' => true,
                'promo_code' => $promo,
                'discount' => $discount,
                'application' => 'cart_wide',
                'invoice_discount' => [
                    'discount_type' => $discount->discount_type,
                    'discount_amount' => (float) $discount->discount_amount,
                    'max_discount_amount' => $discount->max_discount_amount,
                    'computed_savings' => $savings,
                ],
                'line_updates' => [],
                'savings' => $savings,
                'promo_code_id' => $promo->id,
                'promo_code_text' => $promo->code,
            ];
        }

        // eligible_lines
        $line_updates = [];
        $savings = 0.0;
        foreach ($cart_lines as $idx => $line) {
            $variation_id = (int) ($line['variation_id'] ?? 0);
            if (! $variation_id) {
                continue;
            }
            $variation = Variation::with('product')->find($variation_id);
            if (empty($variation) || empty($variation->product)) {
                continue;
            }
            if (! $this->resolver->variationMatchesDiscount($discount, $variation->product, $variation_id)) {
                continue;
            }

            $unit = (float) ($line['unit_price_inc_tax'] ?? $variation->sell_price_inc_tax);
            $qty = (float) ($line['quantity'] ?? 1);
            $line_save = $this->resolver->computeLineSavings($unit, $discount->discount_type, (float) $discount->discount_amount, $qty);
            $savings += $line_save;

            $line_updates[] = [
                'index' => $idx,
                'variation_id' => $variation_id,
                'discount_id' => $discount->id,
                'line_discount_type' => $discount->discount_type,
                'line_discount_amount' => (float) $discount->discount_amount,
                'savings' => $line_save,
            ];
        }

        if (empty($line_updates)) {
            return ['success' => false, 'msg' => __('lang_v1.promo_code_no_eligible_products')];
        }

        if (! empty($discount->max_discount_amount) && $savings > (float) $discount->max_discount_amount) {
            $savings = (float) $discount->max_discount_amount;
        }

        return [
            'success' => true,
            'promo_code' => $promo,
            'discount' => $discount,
            'application' => 'eligible_lines',
            'invoice_discount' => null,
            'line_updates' => $line_updates,
            'savings' => $savings,
            'promo_code_id' => $promo->id,
            'promo_code_text' => $promo->code,
        ];
    }

    /**
     * Consume promo code after a final sale (call inside DB transaction).
     */
    public function consume(PromoCode $promo, Transaction $transaction, float $savings = 0, string $source = 'promo_code'): void
    {
        $locked = PromoCode::where('id', $promo->id)->lockForUpdate()->first();
        if (empty($locked)) {
            return;
        }

        $max_total = $locked->is_single_use ? 1 : $locked->max_uses_total;
        if (! is_null($max_total) && (int) $locked->used_count >= (int) $max_total) {
            throw new \RuntimeException(__('lang_v1.promo_code_exhausted'));
        }

        $locked->used_count = (int) $locked->used_count + 1;
        $locked->save();

        DiscountRedemption::create([
            'discount_id' => $locked->discount_id,
            'promo_code_id' => $locked->id,
            'redemption_source' => $source,
            'transaction_id' => $transaction->id,
            'contact_id' => $transaction->contact_id,
            'qty' => 0,
            'discount_amount' => $savings,
            'business_id' => $transaction->business_id,
        ]);
    }

    /**
     * Record automatic / flash discount redemptions and bump flash_sold_qty.
     */
    public function recordAutomaticRedemptions(Transaction $transaction, array $sell_lines_input = []): void
    {
        $transaction->loadMissing('sell_lines');

        foreach ($transaction->sell_lines as $line) {
            if (empty($line->discount_id)) {
                continue;
            }

            $discount = Discount::find($line->discount_id);
            if (empty($discount) || ! empty($discount->requires_promo_code)) {
                continue;
            }

            $line_saving = 0;
            if (method_exists($line, 'get_discount_amount')) {
                $line_saving = $line->get_discount_amount() * (float) $line->quantity;
            }

            DiscountRedemption::create([
                'discount_id' => $discount->id,
                'promo_code_id' => null,
                'redemption_source' => 'automatic',
                'transaction_id' => $transaction->id,
                'transaction_sell_line_id' => $line->id,
                'contact_id' => $transaction->contact_id,
                'variation_id' => $line->variation_id,
                'qty' => $line->quantity,
                'discount_amount' => $line_saving,
                'business_id' => $transaction->business_id,
            ]);

            if ($discount->discount_kind === 'flash_sale') {
                $discount->flash_sold_qty = (float) $discount->flash_sold_qty + (float) $line->quantity;
                $discount->save();
            }
        }
    }

    /**
     * Restore promo use on sell return when configured.
     */
    public function restoreOnReturn(Transaction $originalSell): void
    {
        if (empty($originalSell->promo_code_id)) {
            return;
        }

        $promo = PromoCode::find($originalSell->promo_code_id);
        if (empty($promo) || empty($promo->restore_on_return)) {
            return;
        }

        DB::transaction(function () use ($promo, $originalSell) {
            $locked = PromoCode::where('id', $promo->id)->lockForUpdate()->first();
            if ($locked && (int) $locked->used_count > 0) {
                $locked->used_count = (int) $locked->used_count - 1;
                $locked->save();
            }

            DiscountRedemption::create([
                'discount_id' => $promo->discount_id,
                'promo_code_id' => $promo->id,
                'redemption_source' => 'promo_code',
                'transaction_id' => $originalSell->id,
                'contact_id' => $originalSell->contact_id,
                'qty' => 0,
                'discount_amount' => -1 * (float) ($originalSell->promo_code_discount_total ?? 0),
                'business_id' => $originalSell->business_id,
            ]);
        });
    }

    protected function isWithinWindow(PromoCode $promo, Discount $discount, Carbon $now): bool
    {
        $start = $discount->starts_at;
        $end = $discount->ends_at;

        if (! empty($promo->starts_at) && (empty($start) || $promo->starts_at->gt($start))) {
            $start = $promo->starts_at;
        }
        if (! empty($promo->ends_at) && (empty($end) || $promo->ends_at->lt($end))) {
            $end = $promo->ends_at;
        }

        if (! empty($start) && $now->lt($start)) {
            return false;
        }
        if (! empty($end) && $now->gt($end)) {
            return false;
        }

        return true;
    }

    protected function passesCustomerGroup(Discount $discount, $customer_group_id): bool
    {
        $mode = $discount->cg_mode ?: 'any';

        if ($mode === 'any') {
            return true;
        }

        if ($mode === 'with_group_only') {
            return ! empty($customer_group_id);
        }

        if ($mode === 'specific_groups') {
            if (empty($customer_group_id)) {
                return false;
            }

            return $discount->customerGroups->contains('id', (int) $customer_group_id);
        }

        if ($mode === 'exclude_groups') {
            if (empty($customer_group_id)) {
                return true;
            }

            return ! $discount->customerGroups->contains('id', (int) $customer_group_id);
        }

        // legacy
        if (! empty($discount->applicable_in_cg) && empty($customer_group_id)) {
            return false;
        }

        return true;
    }

    protected function computeCartWideSavings(float $cart_subtotal, Discount $discount): float
    {
        $savings = 0.0;
        if ($discount->discount_type === 'fixed') {
            $savings = min($cart_subtotal, (float) $discount->discount_amount);
        } else {
            $savings = ($cart_subtotal * (float) $discount->discount_amount) / 100;
            if (! empty($discount->max_discount_amount) && $savings > (float) $discount->max_discount_amount) {
                $savings = (float) $discount->max_discount_amount;
            }
        }

        return max(0, $savings);
    }

    /**
     * Apply validated cart_wide result into invoice discount array for calculateInvoiceTotal.
     */
    public function invoiceDiscountFromValidation(array $validation): ?array
    {
        if (empty($validation['success']) || ($validation['application'] ?? '') !== 'cart_wide') {
            return null;
        }

        $inv = $validation['invoice_discount'];
        // If percentage capped, convert to fixed savings for simplicity
        if (($inv['discount_type'] ?? '') === 'percentage' && ! empty($inv['max_discount_amount'])) {
            $computed = (float) ($inv['computed_savings'] ?? 0);
            $uncapped = null; // already capped in computeCartWideSavings

            return [
                'discount_type' => 'fixed',
                'discount_amount' => $computed,
            ];
        }

        return [
            'discount_type' => $inv['discount_type'],
            'discount_amount' => $inv['discount_amount'],
        ];
    }
}
