<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Contact;
use App\Transaction;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class StoreCheckoutController extends Controller
{
    public function __construct(
        private TransactionUtil $transactionUtil,
        private ProductUtil $productUtil,
        private NotificationUtil $notificationUtil
    ) {}

    public function show(Request $request)
    {
        $customer = auth('customer')->user();
        $business_id = $customer->business_id;
        $location_id = $this->resolveLocationId($business_id, $request);

        $selected_variation_id = $request->integer('variation_id');
        $selected_qty = max(1, $request->integer('qty', 1));
        $variation = null;
        if (! empty($selected_variation_id)) {
            $variation = Variation::where('id', $selected_variation_id)
                ->whereHas('product', function ($q) use ($business_id) {
                    $q->where('business_id', $business_id)->active()->productForSales();
                })
                ->with(['product', 'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }])
                ->first();
        }

        return view('frontend.store.checkout')->with([
            'variation' => $variation,
            'qty' => $selected_qty,
            'location_id' => $location_id,
            'customer' => $customer,
        ]);
    }

    public function checkout(Request $request)
    {
        $customer = auth('customer')->user();
        $business_id = $customer->business_id;
        $location_id = $this->resolveLocationId($business_id, $request);

        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.variation_id' => 'required|integer',
            'products.*.quantity' => 'required|numeric|min:0.0001',
            'addresses' => 'nullable|array',
            'shipping_address_option' => 'nullable|in:existing,new',
            'addresses.shipping_address.shipping_name' => 'nullable|string|max:191',
            'addresses.shipping_address.shipping_address_line_1' => 'nullable|string|max:255',
            'addresses.shipping_address.shipping_city' => 'nullable|string|max:191',
            'addresses.shipping_address.shipping_state' => 'nullable|string|max:191',
            'addresses.shipping_address.shipping_country' => 'nullable|string|max:191',
            'addresses.shipping_address.shipping_zip_code' => 'nullable|string|max:20',
            'addresses.shipping_address.shipping_mobile' => 'nullable|string|max:30',
            'idempotency_key' => 'nullable|string|max:191',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $customer_contact = Contact::findOrFail($customer->id);
        $resolved_addresses = $this->resolveShippingAddress($validated, $customer_contact, $request);

        if (! empty($validated['idempotency_key'])) {
            $existing = Transaction::where('business_id', $business_id)
                ->where('contact_id', $customer->id)
                ->where('type', 'sell')
                ->where('source', 'ecommerce')
                ->where('ref_no', $validated['idempotency_key'])
                ->first();

            if ($existing) {
                if (! $request->expectsJson()) {
                    return redirect()->route('store.account.orders.show', $existing->id)
                        ->with('status', ['success' => true, 'msg' => 'Order already processed.']);
                }

                return $this->respond([
                    'success' => true,
                    'msg' => 'Order already processed.',
                    'transaction' => $existing,
                ]);
            }
        }

        $variation_map = collect($validated['products'])->keyBy('variation_id');
        $variation_ids = $variation_map->keys()->all();
        $location_id = $this->resolveCheckoutLocationId($business_id, $location_id, $variation_map);
        $variations = $this->getVariationsDetails($business_id, $location_id, $variation_ids);

        $is_valid = true;
        $error_messages = [];
        $sell_lines = [];
        $final_total = 0;

        foreach ($variations as $variation) {
            $requested_qty = (float) ($variation_map[$variation->id]['quantity'] ?? 0);
            $available = (float) optional($variation->variation_location_details->first())->qty_available;

            if ($variation->product->enable_stock == 1 && $available < $requested_qty) {
                $is_valid = false;
                $error_messages[] = 'Only '.$available.' of '.$variation->product->name.' is available.';
            }

            $unit_price = (float) $variation->sell_price_inc_tax;
            $sell_lines[] = [
                'product_id' => $variation->product->id,
                'unit_price_before_discount' => $unit_price,
                'unit_price' => $unit_price,
                'unit_price_inc_tax' => $unit_price,
                'variation_id' => $variation->id,
                'quantity' => $requested_qty,
                'item_tax' => 0,
                'enable_stock' => $variation->product->enable_stock,
                'tax_id' => null,
            ];
            $final_total += ($requested_qty * $unit_price);
        }

        if (! $is_valid) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['products' => implode(' ', $error_messages)])->withInput();
            }

            return $this->respond([
                'success' => false,
                'error_messages' => $error_messages,
            ]);
        }

        $business = Business::findOrFail($business_id);
        $user_id = $business->owner_id;
        $order_data = [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'contact_id' => $customer->id,
            'final_total' => $final_total,
            'created_by' => $user_id,
            'status' => 'final',
            'sub_status' => 'ecommerce_new',
            'ecommerce_order_status' => 'new',
            'shipping_status' => 'ordered',
            'payment_status' => 'due',
            'additional_notes' => '',
            'transaction_date' => now(),
            'customer_group_id' => $customer_contact->customer_group_id,
            'tax_rate_id' => null,
            'sale_note' => null,
            'commission_agent' => null,
            'order_addresses' => json_encode($resolved_addresses),
            'products' => $sell_lines,
            'is_created_from_api' => 1,
            'source' => 'ecommerce',
            'ref_no' => $validated['idempotency_key'] ?? ('ecom_'.uniqid()),
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ];

        $invoice_total = ['total_before_tax' => $final_total, 'tax' => 0];
        $business_data = [
            'id' => $business_id,
            'accounting_method' => $business->accounting_method,
            'location_id' => $location_id,
        ];

        DB::beginTransaction();
        try {
            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);
            // Ensure ecommerce-only status is persisted even if util ignores unknown keys.
            $transaction->ecommerce_order_status = 'new';
            $transaction->sub_status = 'ecommerce_new';
            $transaction->save();
            $this->transactionUtil->createOrUpdateSellLines($transaction, $order_data['products'], $location_id, false, null, [], false);

            foreach ($order_data['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $location_id,
                        $product['quantity']
                    );
                }
            }

            $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            if (! $request->expectsJson()) {
                return back()->withErrors(['checkout' => __('messages.something_went_wrong')])->withInput();
            }

            return $this->respondWentWrong($e);
        }

        if (! $request->expectsJson()) {
            return redirect()->route('store.account.orders.show', $transaction->id)
                ->with('status', ['success' => true, 'msg' => 'Order placed successfully.']);
        }

        return $this->respond([
            'success' => true,
            'msg' => 'Order placed successfully.',
            'transaction_id' => $transaction->id,
            'invoice_no' => $transaction->invoice_no,
            'payment_status' => $transaction->payment_status,
            'shipping_status' => $transaction->shipping_status,
        ]);
    }

    private function getVariationsDetails(int $business_id, int $location_id, array $variation_ids)
    {
        return Variation::whereIn('id', $variation_ids)
            ->with([
                'product' => function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                },
                'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                },
            ])
            ->get();
    }

    private function resolveLocationId(int $business_id, Request $request): int
    {
        if ($request->filled('location_id')) {
            return (int) $request->input('location_id');
        }

        return (int) \App\BusinessLocation::where('business_id', $business_id)->value('id');
    }

    private function resolveCheckoutLocationId(int $business_id, int $preferred_location_id, \Illuminate\Support\Collection $variation_map): int
    {
        $variation_ids = $variation_map->keys()->map(fn ($id) => (int) $id)->all();
        if (empty($variation_ids)) {
            return $preferred_location_id;
        }

        $location_ids = \App\BusinessLocation::where('business_id', $business_id)->pluck('id');
        if ($location_ids->isEmpty()) {
            return $preferred_location_id;
        }

        $rows = DB::table('variation_location_details')
            ->whereIn('location_id', $location_ids->all())
            ->whereIn('variation_id', $variation_ids)
            ->select('location_id', 'variation_id', 'qty_available')
            ->get()
            ->groupBy('location_id');

        $ordered_locations = collect([$preferred_location_id])
            ->merge($location_ids->all())
            ->unique()
            ->values();

        foreach ($ordered_locations as $location_id) {
            $location_stock = collect($rows->get($location_id, []))->keyBy('variation_id');
            $can_fulfill_all = true;

            foreach ($variation_ids as $variation_id) {
                $requested_qty = (float) ($variation_map[$variation_id]['quantity'] ?? 0);
                $available_qty = (float) optional($location_stock->get($variation_id))->qty_available;
                if ($available_qty < $requested_qty) {
                    $can_fulfill_all = false;
                    break;
                }
            }

            if ($can_fulfill_all) {
                return (int) $location_id;
            }
        }

        return $preferred_location_id;
    }

    private function resolveShippingAddress(array $validated, Contact $customer_contact, Request $request): array
    {
        $option = $validated['shipping_address_option'] ?? null;
        $provided = $validated['addresses']['shipping_address'] ?? [];

        if ($option === 'new' || (! empty($provided) && empty($option))) {
            $name = trim((string) ($provided['shipping_name'] ?? ''));
            $line1 = trim((string) ($provided['shipping_address_line_1'] ?? ''));

            if ($name === '' || $line1 === '') {
                throw ValidationException::withMessages([
                    'addresses.shipping_address.shipping_address_line_1' => __('Shipping name and address are required.'),
                ]);
            }

            return [
                'shipping_address' => [
                    'shipping_name' => $name,
                    'shipping_address_line_1' => $line1,
                    'shipping_city' => (string) ($provided['shipping_city'] ?? ''),
                    'shipping_state' => (string) ($provided['shipping_state'] ?? ''),
                    'shipping_country' => (string) ($provided['shipping_country'] ?? ''),
                    'shipping_zip_code' => (string) ($provided['shipping_zip_code'] ?? ''),
                    'shipping_mobile' => (string) ($provided['shipping_mobile'] ?? ''),
                ],
            ];
        }

        $has_existing_address = ! empty(trim((string) $customer_contact->shipping_address));
        if (! $has_existing_address) {
            throw ValidationException::withMessages([
                'shipping_address_option' => __('No saved shipping address found. Please enter a new shipping address.'),
            ]);
        }

        return [
            'shipping_address' => [
                'shipping_name' => (string) ($customer_contact->name ?? ''),
                'shipping_address_line_1' => (string) ($customer_contact->shipping_address ?? ''),
                'shipping_city' => (string) ($customer_contact->city ?? ''),
                'shipping_state' => (string) ($customer_contact->state ?? ''),
                'shipping_country' => (string) ($customer_contact->country ?? ''),
                'shipping_zip_code' => (string) ($customer_contact->zip_code ?? ''),
                'shipping_mobile' => (string) ($customer_contact->mobile ?? ''),
            ],
        ];
    }
}

