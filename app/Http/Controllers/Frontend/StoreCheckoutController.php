<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Contact;
use App\Events\StockTransferCreatedOrModified;
use App\Transaction;
use App\ServoOrderLog;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Services\Tab3eenOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class StoreCheckoutController extends Controller
{
    public function __construct(
        private TransactionUtil $transactionUtil,
        private ProductUtil $productUtil,
        private NotificationUtil $notificationUtil,
        private Tab3eenOrderService $tab3eenOrderService
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
            'products.*.product_id' => 'nullable|integer',
            'products.*.source' => 'nullable|in:servo',
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
        [$servo_products, $local_products] = $this->splitCheckoutProducts($validated['products'], $business_id);
        $servo_client_name = trim((string) ($resolved_addresses['shipping_address']['shipping_name'] ?? $customer_contact->name ?? ''));

        if (empty($local_products) && ! empty($servo_products) && ! empty($validated['idempotency_key'])) {
            $existing_servo_log = ServoOrderLog::where('business_id', $business_id)
                ->where('contact_id', $customer->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existing_servo_log && $existing_servo_log->status === 'success') {
                return $this->checkoutSuccessResponse(
                    $request,
                    null,
                    __('storefront.checkout.success')
                );
            }
        }

        $has_local = ! empty($local_products);
        $has_servo = ! empty($servo_products);
        $local_result = null;
        $servo_result = null;

        if ($has_local) {
            $validated['products'] = $local_products;
            $local_result = $this->processLocalStoreCheckout(
                $request,
                $validated,
                $customer,
                $customer_contact,
                $business_id,
                $location_id,
                $resolved_addresses
            );
        }

        if ($has_servo) {
            $servo_result = $this->recordServoOrderAttempt(
                $servo_products,
                $customer_contact,
                $business_id,
                $servo_client_name,
                isset($local_result['transaction']) ? $local_result['transaction']->id : null,
                $validated['idempotency_key'] ?? null
            );
        }

        return $this->buildCheckoutOutcomeResponse(
            $request,
            $has_local,
            $has_servo,
            $local_result,
            $servo_result
        );
    }

    /**
     * @return array{success: bool, transaction?: Transaction, errors?: array<int, string>, already_processed?: bool}
     */
    private function processLocalStoreCheckout(
        Request $request,
        array $validated,
        Contact $customer,
        Contact $customer_contact,
        int $business_id,
        int $location_id,
        array $resolved_addresses
    ) {
        if (! empty($validated['idempotency_key'])) {
            $existing = Transaction::where('business_id', $business_id)
                ->where('contact_id', $customer->id)
                ->where('type', 'sell')
                ->where('source', 'ecommerce')
                ->where('ref_no', $validated['idempotency_key'])
                ->first();

            if ($existing) {
                return [
                    'success' => true,
                    'transaction' => $existing,
                    'already_processed' => true,
                ];
            }
        }

        $variation_map = collect($validated['products'])->keyBy('variation_id');
        $variation_ids = $variation_map->keys()->map(fn ($id) => (int) $id)->all();
        $variations_by_id = $this->loadStorefrontVariationsById($business_id, $variation_ids);

        if ($variations_by_id->count() !== count($variation_ids)) {
            return [
                'success' => false,
                'errors' => [__('This product is no longer available for purchase.')],
            ];
        }

        $stock_matrix = $this->buildVariationLocationStockMatrix($business_id, $variation_ids);

        $network_errors = $this->validateNetworkStockForCart($variation_map, $stock_matrix, $variations_by_id);
        if (! empty($network_errors)) {
            return [
                'success' => false,
                'errors' => $network_errors,
            ];
        }

        $fulfillment_location_id = $this->resolveCheckoutLocationId($business_id, $location_id, $variation_map);

        $business = Business::findOrFail($business_id);
        $user_id = $business->owner_id;

        $sell_lines = [];
        $final_total = 0;
        $is_valid = true;
        $error_messages = [];

        DB::beginTransaction();
        try {
            $stock_matrix = $this->buildVariationLocationStockMatrix($business_id, $variation_ids);
            $this->consolidateInventoryToFulfillmentLocation(
                $business_id,
                $fulfillment_location_id,
                $variation_map,
                $variations_by_id,
                $stock_matrix,
                $user_id,
                $business
            );

            $location_id = $fulfillment_location_id;
            $variations = $this->getVariationsDetails($business_id, $location_id, $variation_ids);

            if ($variations->count() !== count($variation_ids)) {
                $is_valid = false;
                $error_messages[] = __('This product is no longer available for purchase.');
            }

            foreach ($variations as $variation) {
                $product = $variation->product;
                if (empty($product)) {
                    $is_valid = false;
                    $error_messages[] = __('This product is no longer available for purchase.');

                    continue;
                }

                $requested_qty = (float) ($variation_map[$variation->id]['quantity'] ?? 0);
                $available = (float) optional($variation->variation_location_details->first())->qty_available;

                if ((int) $product->enable_stock === 1 && $available + 0.0001 < $requested_qty) {
                    $is_valid = false;
                    $error_messages[] = 'Only '.$available.' of '.$product->name.' is available at the fulfillment location after stock moves.';
                }

                $unit_price = (float) $variation->sell_price_inc_tax;
                $sell_lines[] = [
                    'product_id' => $product->id,
                    'unit_price_before_discount' => $unit_price,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price,
                    'variation_id' => $variation->id,
                    'quantity' => $requested_qty,
                    'item_tax' => 0,
                    'enable_stock' => $product->enable_stock,
                    'tax_id' => null,
                ];
                $final_total += ($requested_qty * $unit_price);
            }

            if (! $is_valid) {
                DB::rollBack();

                return [
                    'success' => false,
                    'errors' => array_values(array_unique($error_messages)),
                ];
            }

            $order_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $customer->id,
                'final_total' => $final_total,
                'created_by' => $user_id,
                'status' => 'final',
                'sub_status' => 'ecommerce_new',
                'ecommerce_order_status' => 'pending',
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
                'is_direct_sale' => 1,
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

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);
            // Ensure ecommerce-only status is persisted even if util ignores unknown keys.
            $transaction->ecommerce_order_status = 'pending';
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

            return [
                'success' => false,
                'errors' => [__('messages.something_went_wrong')],
            ];
        }

        return [
            'success' => true,
            'transaction' => $transaction,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $servo_products
     * @return array{success: bool, servo_reference?: string|null, error?: string}
     */
    private function recordServoOrderAttempt(
        array $servo_products,
        Contact $customer_contact,
        int $business_id,
        string $client_name,
        ?int $transaction_id,
        ?string $idempotency_key
    ): array {
        if ($transaction_id !== null) {
            $existing = ServoOrderLog::where('transaction_id', $transaction_id)->first();
            if ($existing) {
                return $this->servoAttemptResultFromLog($existing);
            }
        } elseif (! empty($idempotency_key)) {
            $existing = ServoOrderLog::where('business_id', $business_id)
                ->where('contact_id', $customer_contact->id)
                ->where('idempotency_key', $idempotency_key)
                ->first();
            if ($existing) {
                return $this->servoAttemptResultFromLog($existing);
            }
        }

        if ($client_name === '') {
            return [
                'success' => false,
                'error' => __('Customer name is required for Servo orders.'),
            ];
        }

        try {
            $items = $this->normalizeServoOrderItems($servo_products);
        } catch (ValidationException $e) {
            $error_message = collect($e->errors())->flatten()->first() ?: __('This product is no longer available for purchase.');

            ServoOrderLog::create([
                'business_id' => $business_id,
                'contact_id' => $customer_contact->id,
                'transaction_id' => $transaction_id,
                'idempotency_key' => $idempotency_key,
                'client_name' => $client_name,
                'items' => $servo_products,
                'status' => 'failed',
                'error_message' => $error_message,
            ]);

            Log::warning('Servo order payload validation failed and was logged internally.', [
                'transaction_id' => $transaction_id,
                'contact_id' => $customer_contact->id,
                'error_message' => $error_message,
            ]);

            return [
                'success' => false,
                'error' => $this->customerFacingServoError($error_message),
            ];
        }

        $log = ServoOrderLog::create([
            'business_id' => $business_id,
            'contact_id' => $customer_contact->id,
            'transaction_id' => $transaction_id,
            'idempotency_key' => $idempotency_key,
            'client_name' => $client_name,
            'items' => $items,
            'status' => 'pending',
        ]);

        $result = $this->tab3eenOrderService->submitOrder($items, $client_name);

        $log->update([
            'status' => $result['success'] ? 'success' : 'failed',
            'request_payload' => $result['request_payload'],
            'response_payload' => $result['response_payload'],
            'error_message' => $result['error_message'],
            'http_status' => $result['http_status'],
            'servo_reference' => $result['servo_reference'],
        ]);

        if (! $result['success']) {
            Log::warning('Servo order failed internally after checkout success.', [
                'servo_order_log_id' => $log->id,
                'transaction_id' => $transaction_id,
                'contact_id' => $customer_contact->id,
                'error_message' => $result['error_message'],
            ]);

            return [
                'success' => false,
                'error' => $this->customerFacingServoError($result['error_message']),
            ];
        }

        return [
            'success' => true,
            'servo_reference' => $result['servo_reference'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function splitCheckoutProducts(array $products, int $business_id): array
    {
        $variation_ids = collect($products)
            ->pluck('variation_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $local_variation_ids = empty($variation_ids)
            ? []
            : Variation::whereIn('id', $variation_ids)
                ->whereHas('product', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();

        $servo_products = [];
        $local_products = [];

        foreach ($products as $product) {
            $variation_id = (int) ($product['variation_id'] ?? 0);
            $is_servo = ($product['source'] ?? null) === 'servo'
                || ! isset($local_variation_ids[$variation_id]);

            if ($is_servo) {
                $servo_products[] = array_merge($product, ['source' => 'servo']);
            } else {
                $local_products[] = $product;
            }
        }

        return [$servo_products, $local_products];
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array{product_id: int, variation_id: int, quantity: float}>
     */
    private function normalizeServoOrderItems(array $products): array
    {
        return collect($products)->map(function ($product) {
            $product_id = (int) ($product['product_id'] ?? $product['id'] ?? 0);
            $variation_id = (int) ($product['variation_id'] ?? 0);
            $quantity = (float) ($product['quantity'] ?? 0);

            if ($product_id <= 0 || $variation_id <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages([
                    'products' => __('This product is no longer available for purchase.'),
                ]);
            }

            return [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>|null  $local_result
     * @param  array<string, mixed>|null  $servo_result
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    private function buildCheckoutOutcomeResponse(
        Request $request,
        bool $has_local,
        bool $has_servo,
        ?array $local_result,
        ?array $servo_result
    ) {
        $local_ok = ! $has_local || ($local_result['success'] ?? false);
        $servo_ok = ! $has_servo || ($servo_result['success'] ?? false);
        $transaction = $local_result['transaction'] ?? null;

        if ($local_ok && $servo_ok) {
            return $this->checkoutSuccessResponse(
                $request,
                $transaction,
                __('storefront.checkout.success')
            );
        }

        if (! $local_ok && ! $servo_ok) {
            return $this->checkoutFailureResponse($request, $local_result, $servo_result, $has_local, $has_servo);
        }

        return $this->checkoutPartialSuccessResponse(
            $request,
            $transaction,
            $local_result,
            $servo_result,
            $has_local,
            $has_servo,
            $local_ok,
            $servo_ok
        );
    }

    /**
     * @param  array<string, mixed>|null  $local_result
     * @param  array<string, mixed>|null  $servo_result
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    private function checkoutFailureResponse(
        Request $request,
        ?array $local_result,
        ?array $servo_result,
        bool $has_local,
        bool $has_servo
    ) {
        $messages = [];

        if ($has_local && ! ($local_result['success'] ?? false)) {
            $messages = array_merge($messages, $local_result['errors'] ?? [__('messages.something_went_wrong')]);
        }

        if ($has_servo && ! ($servo_result['success'] ?? false)) {
            $messages[] = $servo_result['error'] ?? __('storefront.checkout.servo_failed_generic');
        }

        $messages = array_values(array_unique(array_filter($messages)));
        $summary = implode(' ', $messages);

        if (! $request->expectsJson()) {
            return back()->withErrors(['checkout' => $summary])->withInput();
        }

        return $this->respond([
            'success' => false,
            'msg' => $summary,
            'error_messages' => $messages,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $local_result
     * @param  array<string, mixed>|null  $servo_result
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    private function checkoutPartialSuccessResponse(
        Request $request,
        ?Transaction $transaction,
        ?array $local_result,
        ?array $servo_result,
        bool $has_local,
        bool $has_servo,
        bool $local_ok,
        bool $servo_ok
    ) {
        $warnings = [];
        $success_parts = [];

        if ($local_ok && $has_local) {
            $success_parts[] = __('storefront.checkout.partial_local_placed');
        }

        if ($servo_ok && $has_servo) {
            $success_parts[] = __('storefront.checkout.partial_servo_placed');
        }

        if ($has_local && ! $local_ok) {
            $local_error = implode(' ', $local_result['errors'] ?? [__('messages.something_went_wrong')]);
            $warnings[] = __('storefront.checkout.partial_local_failed', ['error' => $local_error]);
        }

        if ($has_servo && ! $servo_ok) {
            $warnings[] = __('storefront.checkout.partial_servo_failed', [
                'error' => $servo_result['error'] ?? __('storefront.checkout.servo_failed_generic'),
            ]);
        }

        $support = $this->customerSupportContactDetails();
        $warnings[] = __('storefront.checkout.contact_customer_service', $support);

        $msg = trim(implode(' ', array_filter([
            __('storefront.checkout.partial_success_title'),
            implode(' ', $success_parts),
        ])));

        return $this->checkoutSuccessResponse($request, $transaction, $msg, [
            'partial' => true,
            'warnings' => $warnings,
            'servo_reference' => $servo_result['servo_reference'] ?? null,
        ]);
    }

    /**
     * @return array{success: bool, servo_reference?: string|null, error?: string}
     */
    private function servoAttemptResultFromLog(ServoOrderLog $log): array
    {
        if ($log->status === 'success') {
            return [
                'success' => true,
                'servo_reference' => $log->servo_reference,
            ];
        }

        return [
            'success' => false,
            'error' => $this->customerFacingServoError($log->error_message),
        ];
    }

    private function customerFacingServoError(?string $error): string
    {
        $error = trim((string) $error);
        if ($error === '') {
            return __('storefront.checkout.servo_failed_generic');
        }

        if (preg_match('/\b(File:|Line:|SQLSTATE|stack trace|exception)\b/i', $error)) {
            return __('storefront.checkout.servo_failed_generic');
        }

        return $error;
    }

    /**
     * @return array{phone: string, email: string}
     */
    private function customerSupportContactDetails(): array
    {
        return [
            'phone' => (string) config('storefront.support_phone', '19900'),
            'email' => (string) config('storefront.support_email', 'info@eltab3een.com'),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function checkoutSuccessResponse(Request $request, ?Transaction $transaction, string $msg, array $extra = [])
    {
        $status = [
            'success' => true,
            'msg' => $msg,
        ];

        if (! empty($extra['partial'])) {
            $status['partial'] = true;
        }

        if (! empty($extra['warnings'])) {
            $status['warnings'] = $extra['warnings'];
        }

        if (! $request->expectsJson()) {
            if ($transaction) {
                return redirect()->route('store.account.orders.show', $transaction->id)
                    ->with('status', $status)
                    ->with('clear_store_cart', true);
            }

            return redirect()->route('welcome')
                ->with('status', $status)
                ->with('clear_store_cart', true);
        }

        return $this->respond(array_merge([
            'success' => true,
            'msg' => $msg,
            'clear_cart' => true,
            'transaction_id' => $transaction?->id,
            'invoice_no' => $transaction?->invoice_no,
            'payment_status' => $transaction?->payment_status,
            'shipping_status' => $transaction?->shipping_status,
        ], $extra));
    }

    private function getVariationsDetails(int $business_id, int $location_id, array $variation_ids)
    {
        // Use whereHas so we only load variations whose product belongs to this business.
        // Do not constrain the eager-loaded `product` relation with where() — that can yield null
        // product on the model even when the variation row exists.
        return Variation::whereIn('id', $variation_ids)
            ->whereHas('product', function ($q) use ($business_id) {
                $q->where('business_id', $business_id)
                    ->active()
                    ->productForSales();
            })
            ->with([
                'product',
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

        return (int) \App\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->value('id');
    }

    private function resolveCheckoutLocationId(int $business_id, int $preferred_location_id, \Illuminate\Support\Collection $variation_map): int
    {
        $variation_ids = $variation_map->keys()->map(fn ($id) => (int) $id)->all();
        if (empty($variation_ids)) {
            return $preferred_location_id;
        }

        $location_ids = \App\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->pluck('id');
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

    /**
     * @return Collection<int, Variation>
     */
    private function loadStorefrontVariationsById(int $business_id, array $variation_ids): Collection
    {
        if (empty($variation_ids)) {
            return collect();
        }


        return Variation::whereIn('id', $variation_ids)
            ->whereHas('product', function ($q) use ($business_id) {
                $q->where('business_id', $business_id)
                    ->active()
                    ->productForSales();
            })
            ->with('product')
            ->get()
            ->keyBy('id');

    }

    /**
     * @return array<int, array<int, float>> variation_id => location_id => qty_available
     */
    private function buildVariationLocationStockMatrix(int $business_id, array $variation_ids): array
    {
        $variation_ids = array_values(array_unique(array_map('intval', $variation_ids)));
        $matrix = [];
        foreach ($variation_ids as $vid) {
            $matrix[$vid] = [];
        }
        if (empty($variation_ids)) {
            return $matrix;
        }

        $location_ids = \App\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($variation_ids as $vid) {
            foreach ($location_ids as $lid) {
                $matrix[$vid][$lid] = 0.0;
            }
        }

        $rows = DB::table('variation_location_details')
            ->whereIn('location_id', $location_ids)
            ->whereIn('variation_id', $variation_ids)
            ->get(['variation_id', 'location_id', 'qty_available']);

        foreach ($rows as $row) {
            $vid = (int) $row->variation_id;
            $lid = (int) $row->location_id;
            if (! isset($matrix[$vid])) {
                $matrix[$vid] = [];
            }
            $matrix[$vid][$lid] = (float) $row->qty_available;
        }

        return $matrix;
    }

    /**
     * @param  array<int, array<int, float>>  $stock_matrix
     * @return array<int, string>
     */
    private function validateNetworkStockForCart(Collection $variation_map, array $stock_matrix, Collection $variations_by_id): array
    {
        $errors = [];
        foreach ($variation_map as $variation_id => $row) {
            $vid = (int) $variation_id;
            $requested_qty = (float) ($row['quantity'] ?? 0);
            $variation = $variations_by_id->get($vid);
            if (! $variation || ! $variation->product) {
                $errors[] = __('This product is no longer available for purchase.');

                continue;
            }
            if ((int) $variation->product->enable_stock !== 1) {
                continue;
            }
            $sum = array_sum($stock_matrix[$vid] ?? []);
            if ($sum + 0.0001 < $requested_qty) {
                $qty_label = rtrim(rtrim(number_format($sum, 4, '.', ''), '0'), '.') ?: '0';
                $errors[] = sprintf(
                    'Only %s total units of %s are available across all locations.',
                    $qty_label,
                    $variation->product->name
                );
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<int, array<int, float>>  $stock_matrix  updated in place
     */
    private function consolidateInventoryToFulfillmentLocation(
        int $business_id,
        int $fulfillment_location_id,
        Collection $variation_map,
        Collection $variations_by_id,
        array &$stock_matrix,
        int $user_id,
        Business $business
    ): void {
        foreach ($variation_map as $variation_id => $row) {
            $vid = (int) $variation_id;
            $needed = (float) ($row['quantity'] ?? 0);
            if ($needed <= 0) {
                continue;
            }

            $variation = $variations_by_id->get($vid);
            if (! $variation || ! $variation->product || (int) $variation->product->enable_stock !== 1) {
                continue;
            }

            $at_fulfillment = (float) ($stock_matrix[$vid][$fulfillment_location_id] ?? 0);
            $shortfall = $needed - $at_fulfillment;
            $guard = 0;

            while ($shortfall > 0.000001 && $guard++ < 500) {
                $donor_location_id = null;
                $donor_qty = 0.0;
                foreach ($stock_matrix[$vid] ?? [] as $loc_id => $qty) {
                    if ((int) $loc_id === $fulfillment_location_id) {
                        continue;
                    }
                    if ((float) $qty > $donor_qty + 0.000001) {
                        $donor_qty = (float) $qty;
                        $donor_location_id = (int) $loc_id;
                    }
                }

                if ($donor_location_id === null || $donor_qty <= 0.000001) {
                    throw new \RuntimeException('Unable to consolidate stock for variation '.$vid);
                }

                $move_qty = min($shortfall, $donor_qty);
                $unit_price = (float) ($variation->dpp_inc_tax ?: $variation->sell_price_inc_tax ?: 0);

                $this->executeInternalCompletedStockTransfer(
                    $business_id,
                    $donor_location_id,
                    $fulfillment_location_id,
                    [
                        [
                            'product_id' => (int) $variation->product_id,
                            'variation_id' => $vid,
                            'quantity' => $move_qty,
                            'unit_price' => $unit_price,
                            'enable_stock' => 1,
                        ],
                    ],
                    $user_id,
                    $business
                );

                $stock_matrix[$vid][$donor_location_id] = $donor_qty - $move_qty;
                $stock_matrix[$vid][$fulfillment_location_id] = $at_fulfillment + $move_qty;
                $at_fulfillment = (float) $stock_matrix[$vid][$fulfillment_location_id];
                $shortfall = $needed - $at_fulfillment;
            }
        }
    }

    /**
     * @param  array<int, array{product_id: int, variation_id: int, quantity: float, unit_price: float, enable_stock: int}>  $product_moves
     */
    private function executeInternalCompletedStockTransfer(
        int $business_id,
        int $from_location_id,
        int $to_location_id,
        array $product_moves,
        int $user_id,
        Business $business
    ): void {
        if ($from_location_id === $to_location_id || empty($product_moves)) {
            return;
        }

        $sell_lines = [];
        $purchase_lines = [];
        $products_for_stock = [];

        foreach ($product_moves as $product) {
            $qty = (float) $product['quantity'];
            if ($qty <= 0) {
                continue;
            }
            $unit_price = (float) ($product['unit_price'] ?? 0);
            $sell_line_arr = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'item_tax' => 0,
                'line_total_tax' => 0,
                'tax_id' => null,
                'unit_price' => $unit_price,
                'unit_price_inc_tax' => $unit_price,
            ];
            $purchase_line_arr = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'item_tax' => 0,
                'line_total_tax' => 0,
                'tax_id' => null,
                'purchase_price' => $unit_price,
                'purchase_price_inc_tax' => $unit_price,
            ];
            $sell_lines[] = $sell_line_arr;
            $purchase_lines[] = $purchase_line_arr;
            $products_for_stock[] = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'enable_stock' => ! empty($product['enable_stock']),
            ];
        }

        if (empty($sell_lines)) {
            return;
        }

        $final_total = 0.0;
        foreach ($sell_lines as $sl) {
            $final_total += (float) $sl['quantity'] * (float) $sl['unit_price'];
        }

        $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
        $ref_no = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);

        $input_base = [
            'location_id' => $from_location_id,
            'ref_no' => $ref_no,
            'transaction_date' => now(),
            'additional_notes' => __('Store checkout — stock moved to fulfillment location.'),
            'shipping_charges' => 0,
            'final_total' => $final_total,
            'total_before_tax' => $final_total,
            'business_id' => $business_id,
            'created_by' => $user_id,
            'payment_status' => 'paid',
            'status' => 'final',
            'type' => 'sell_transfer',
        ];

        $sell_transfer = Transaction::create($input_base);

        $purchase_input = array_merge($input_base, [
            'type' => 'purchase_transfer',
            'location_id' => $to_location_id,
            'transfer_parent_id' => $sell_transfer->id,
            'status' => 'received',
        ]);

        $purchase_transfer = Transaction::create($purchase_input);

        $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $sell_transfer->location_id, false, null, [], false);
        $purchase_transfer->purchase_lines()->createMany($purchase_lines);

        foreach ($products_for_stock as $product) {
            if (! $product['enable_stock']) {
                continue;
            }
            $this->productUtil->decreaseProductQuantity(
                $product['product_id'],
                $product['variation_id'],
                $sell_transfer->location_id,
                $product['quantity']
            );
            $this->productUtil->updateProductQuantity(
                $purchase_transfer->location_id,
                $product['product_id'],
                $product['variation_id'],
                $product['quantity'],
                0,
                null,
                false
            );
        }

        $this->productUtil->adjustStockOverSelling($purchase_transfer);

        $business_map = [
            'id' => $business_id,
            'accounting_method' => $business->accounting_method,
            'location_id' => $sell_transfer->location_id,
        ];
        $sell_transfer->load('sell_lines');
        $this->transactionUtil->mapPurchaseSell($business_map, $sell_transfer->sell_lines, 'purchase');

        $this->transactionUtil->activityLog($sell_transfer, 'added');

        try {
            event(new StockTransferCreatedOrModified($sell_transfer, 'added'));
        } catch (\Throwable $e) {
            Log::warning('Stock transfer event failed: '.$e->getMessage());
        }
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