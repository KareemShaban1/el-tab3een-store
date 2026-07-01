<?php

namespace App\Http\Controllers\Frontend;

use App\ServoOrderLog;
use App\Services\Tab3eenCatalogService;
use App\Transaction;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class StoreAccountController extends Controller
{
    public function __construct(
        private Tab3eenCatalogService $catalogService
    ) {}

    public function profile(Request $request)
    {
        $customer = auth('customer')->user();

        if (! $request->expectsJson()) {
            return view('frontend.store.account.profile')->with('customer', $customer);
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'shipping_address' => $customer->shipping_address,
                'city' => $customer->city,
                'state' => $customer->state,
                'country' => $customer->country,
                'zip_code' => $customer->zip_code,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Contact $customer */
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:30',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
        ]);

        $validated['country'] = 'Egypt';
        $customer->fill($validated);
        $customer->save();

        if (! $request->expectsJson()) {
            return back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.profile_updated'),
            ]);
        }

        return $this->respond([
            'success' => true,
            'msg' => __('lang_v1.profile_updated'),
        ]);
    }

    public function orders(Request $request)
    {
        /** @var \App\Contact $customer */
        $customer = auth('customer')->user();
        $orderEntries = $this->buildCustomerOrderEntries($customer->business_id, $customer->id);
        $orders = $this->paginateOrderEntries($orderEntries, $request);

        if (! $request->expectsJson()) {
            return view('frontend.store.account.orders')->with('orders', $orders);
        }

        return $this->respond([
            'success' => true,
            'data' => collect($orders->items())->map(fn ($entry) => $this->serializeOrderEntry($entry))->values()->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function orderDetails(Request $request, int $id)
    {
        /** @var \App\Contact $customer */
        $customer = auth('customer')->user();

        $order = Transaction::where('business_id', $customer->business_id)
            ->where('contact_id', $customer->id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->with(['sell_lines.product', 'sell_lines.variations'])
            ->findOrFail($id);

        $servoOrders = ServoOrderLog::where('business_id', $customer->business_id)
            ->where('contact_id', $customer->id)
            ->where('transaction_id', $order->id)
            ->orderByDesc('id')
            ->get();

        $servoItems = $this->formatServoItems(
            $servoOrders->flatMap(fn (ServoOrderLog $log) => $log->items ?? [])->values()->all()
        );

        if (! $request->expectsJson()) {
            return view('frontend.store.account.order_show', compact('order', 'servoOrders', 'servoItems'));
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'type' => $servoOrders->isNotEmpty() ? 'mixed' : 'local',
                'order' => $order,
                'servo_orders' => $servoOrders,
                'servo_items' => $servoItems,
            ],
        ]);
    }

    public function servoOrderDetails(Request $request, int $id)
    {
        /** @var \App\Contact $customer */
        $customer = auth('customer')->user();

        $servoOrder = ServoOrderLog::where('business_id', $customer->business_id)
            ->where('contact_id', $customer->id)
            ->whereNull('transaction_id')
            ->findOrFail($id);

        $servoItems = $this->formatServoItems($servoOrder->items ?? []);

        if (! $request->expectsJson()) {
            return view('frontend.store.account.servo_order_show', compact('servoOrder', 'servoItems'));
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'type' => 'servo',
                'servo_order' => $servoOrder,
                'servo_items' => $servoItems,
            ],
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function buildCustomerOrderEntries(int $business_id, int $contact_id): Collection
    {
        $localOrders = Transaction::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->withCount('sell_lines')
            ->select('id', 'invoice_no', 'transaction_date', 'final_total', 'status', 'sub_status', 'ecommerce_order_status', 'payment_status', 'shipping_status')
            ->orderByDesc('id')
            ->get();

        $localIds = $localOrders->pluck('id')->all();

        $servoByTransaction = ServoOrderLog::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->whereIn('transaction_id', $localIds)
            ->get()
            ->groupBy('transaction_id');

        $standaloneServoOrders = ServoOrderLog::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->whereNull('transaction_id')
            ->orderByDesc('id')
            ->get();

        $entries = collect();

        foreach ($localOrders as $order) {
            $linkedServo = $servoByTransaction->get($order->id, collect());
            $entries->push($this->mapLocalOrderEntry($order, $linkedServo));
        }

        foreach ($standaloneServoOrders as $servoOrder) {
            $entries->push($this->mapServoOrderEntry($servoOrder));
        }

        return $entries->sortByDesc(fn ($entry) => $entry->sort_at->timestamp)->values();
    }

    /**
     * @param  Collection<int, ServoOrderLog>  $linkedServo
     */
    private function mapLocalOrderEntry(Transaction $order, Collection $linkedServo): object
    {
        $orderStatus = $this->normalizeOrderStatus((string) ($order->ecommerce_order_status ?: $order->sub_status ?: 'new'));
        $localItemsCount = (int) ($order->sell_lines_count ?? 0);

        $servoItemsCount = (int) $linkedServo->sum(fn (ServoOrderLog $log) => count($log->items ?? []));
        $hasServo = $linkedServo->isNotEmpty();

        return (object) [
            'entry_type' => $hasServo ? 'mixed' : 'local',
            'sort_at' => $order->transaction_date,
            'display_id' => $order->invoice_no ?: '#'.$order->id,
            'detail_url' => route('store.account.orders.show', $order->id),
            'status' => $orderStatus,
            'status_label' => $this->servoStatusLabel($orderStatus),
            'date' => $order->transaction_date,
            'total' => (float) $order->final_total,
            'items_count' => $localItemsCount + $servoItemsCount,
            'local_items_count' => $localItemsCount,
            'partner_items_count' => $servoItemsCount,
            'payment_status' => (string) ($order->payment_status ?: 'pending'),
            'shipping_status' => (string) ($order->shipping_status ?: 'pending'),
            'type_label' => $hasServo
                ? __('storefront.orders.type_mixed')
                : __('storefront.orders.type_local'),
            'servo_reference' => $linkedServo->first()?->servo_reference,
            'partner_status' => $hasServo ? (string) ($linkedServo->first()?->status ?? 'pending') : null,
        ];
    }

    private function mapServoOrderEntry(ServoOrderLog $servoOrder): object
    {
        $items = is_array($servoOrder->items) ? $servoOrder->items : [];
        $status = (string) ($servoOrder->status ?? 'pending');

        return (object) [
            'entry_type' => 'servo',
            'sort_at' => $servoOrder->created_at,
            'display_id' => $servoOrder->servo_reference ?: ('#P-'.$servoOrder->id),
            'detail_url' => route('store.account.orders.servo.show', $servoOrder->id),
            'status' => $status,
            'status_label' => $this->servoStatusLabel($status),
            'date' => $servoOrder->created_at,
            'total' => null,
            'items_count' => count($items),
            'local_items_count' => 0,
            'partner_items_count' => count($items),
            'payment_status' => null,
            'shipping_status' => null,
            'type_label' => __('storefront.orders.type_partner'),
            'servo_reference' => $servoOrder->servo_reference,
            'partner_status' => $status,
        ];
    }

    /**
     * @param  Collection<int, object>  $entries
     */
    private function paginateOrderEntries(Collection $entries, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $total = $entries->count();
        $items = $entries->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function formatServoItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $catalogMap = $this->buildCatalogVariationMap();
        $variationIds = collect($items)->pluck('variation_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $localVariations = empty($variationIds)
            ? collect()
            : Variation::whereIn('id', $variationIds)
                ->with(['product', 'product_variation'])
                ->get()
                ->keyBy('id');

        return collect($items)->map(function ($item) use ($catalogMap, $localVariations) {
            $product_id = (int) ($item['product_id'] ?? 0);
            $variation_id = (int) ($item['variation_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $catalogKey = $product_id.'-'.$variation_id;

            $product_name = (string) ($item['name'] ?? '');
            $variation_name = (string) ($item['variation_name'] ?? '');
            $unit_price = isset($item['price']) ? (float) $item['price'] : null;

            if ($product_name === '' && isset($catalogMap[$catalogKey])) {
                $product_name = $catalogMap[$catalogKey]['product_name'];
                $variation_name = $catalogMap[$catalogKey]['variation_name'];
                $unit_price = $unit_price ?? $catalogMap[$catalogKey]['price'];
            }

            if ($product_name === '' && $localVariations->has($variation_id)) {
                $variation = $localVariations->get($variation_id);
                $product_name = optional($variation->product)->name ?: '';
                $variation_name = $variation->name ?: $variation_name;
                $unit_price = $unit_price ?? (float) ($variation->sell_price_inc_tax ?? 0);
            }

            if ($product_name === '') {
                $product_name = __('storefront.orders.product_number', ['id' => $product_id]);
            }

            return [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'product_name' => $product_name,
                'variation_name' => $variation_name,
                'unit_price' => $unit_price,
                'line_total' => $unit_price !== null ? $quantity * $unit_price : null,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, array{product_name: string, variation_name: string, price: float|null}>
     */
    private function buildCatalogVariationMap(): array
    {
        $map = [];

        foreach ($this->catalogService->getCatalog() as $category) {
            foreach ($category['products'] ?? [] as $product) {
                foreach ($product['variations'] ?? [] as $variation) {
                    $productId = (int) ($product['id'] ?? 0);
                    $variationId = (int) ($variation['variation_id'] ?? 0);
                    if ($productId <= 0 || $variationId <= 0) {
                        continue;
                    }

                    $map[$productId.'-'.$variationId] = [
                        'product_name' => (string) ($product['name'] ?? ''),
                        'variation_name' => (string) ($variation['name'] ?? ''),
                        'price' => isset($variation['price']) ? (float) $variation['price'] : null,
                    ];
                }
            }
        }

        return $map;
    }

    private function normalizeOrderStatus(string $status): string
    {
        if (str_starts_with($status, 'ecommerce_')) {
            return substr($status, 10);
        }

        return $status;
    }

    private function servoStatusLabel(string $status): string
    {
        $key = 'storefront.orders.status_'.strtolower($status);

        return __($key) !== $key ? __($key) : ucfirst(str_replace('_', ' ', $status));
    }

    private function serializeOrderEntry(object $entry): array
    {
        return [
            'entry_type' => $entry->entry_type,
            'display_id' => $entry->display_id,
            'detail_url' => $entry->detail_url,
            'status' => $entry->status,
            'status_label' => $entry->status_label,
            'date' => optional($entry->date)->toDateTimeString(),
            'total' => $entry->total,
            'items_count' => $entry->items_count,
            'local_items_count' => $entry->local_items_count,
            'partner_items_count' => $entry->partner_items_count,
            'payment_status' => $entry->payment_status,
            'shipping_status' => $entry->shipping_status,
            'type_label' => $entry->type_label,
            'servo_reference' => $entry->servo_reference,
            'partner_status' => $entry->partner_status,
        ];
    }
}
