<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Tab3eenOrderService
{
    /**
     * Submit an order to the Servo store API without throwing.
     *
     * @param  array<int, array{product_id: int, variation_id: int, quantity: float|int}>  $items
     * @return array{
     *     success: bool,
     *     http_status: int|null,
     *     request_payload: array<string, mixed>,
     *     response_payload: array<string, mixed>|null,
     *     error_message: string|null,
     *     servo_reference: string|null
     * }
     */
    public function submitOrder(array $items, string $clientName): array
    {
        $url = $this->ordersApiUrl();
        $request_payload = [
            'items' => collect($items)->map(function ($item) use ($clientName) {
                return [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'variation_id' => (int) ($item['variation_id'] ?? 0),
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'tab3een_client_name' => $clientName,
                ];
            })->values()->all(),
        ];

        if ($url === '') {
            return $this->failedResult($request_payload, null, 'Tab3een orders API URL is not configured.');
        }

        if ($clientName === '') {
            return $this->failedResult($request_payload, null, 'Customer name is required for Servo orders.');
        }

        if (empty($items)) {
            return $this->failedResult($request_payload, null, 'Servo order must include at least one item.');
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($url, $request_payload);
        } catch (\Throwable $e) {
            Log::warning('Tab3een orders API exception: '.$e->getMessage(), [
                'url' => $url,
            ]);

            return $this->failedResult($request_payload, null, $e->getMessage());
        }

        $http_status = $response->status();
        $response_payload = $response->json();
        if (! is_array($response_payload)) {
            $response_payload = ['raw' => $response->body()];
        }

        if (! $response->successful()) {
            Log::warning('Tab3een orders API request failed.', [
                'url' => $url,
                'status' => $http_status,
                'body' => $response->body(),
            ]);

            $message = data_get($response_payload, 'message', $response->body());

            return $this->failedResult(
                $request_payload,
                $http_status,
                is_string($message) && $message !== '' ? $message : 'Servo order request failed.',
                $response_payload
            );
        }

        if (($response_payload['status'] ?? null) !== 'success') {
            $message = (string) ($response_payload['message'] ?? 'Servo order was not accepted.');

            return $this->failedResult($request_payload, $http_status, $message, $response_payload);
        }

        return [
            'success' => true,
            'http_status' => $http_status,
            'request_payload' => $request_payload,
            'response_payload' => $response_payload,
            'error_message' => null,
            'servo_reference' => $this->extractServoReference($response_payload),
        ];
    }

    public function ordersApiUrl(): string
    {
        $ordersUrl = trim((string) config('storefront.tab3een_orders_api_url', ''));
        if ($ordersUrl !== '') {
            return $ordersUrl;
        }

        $catalogUrl = rtrim(trim((string) config('storefront.tab3een_catalog_api_url', '')), '/');

        return $catalogUrl !== '' ? $catalogUrl.'/orders' : '';
    }

    /**
     * @param  array<string, mixed>|null  $response_payload
     * @return array{
     *     success: bool,
     *     http_status: int|null,
     *     request_payload: array<string, mixed>,
     *     response_payload: array<string, mixed>|null,
     *     error_message: string|null,
     *     servo_reference: string|null
     * }
     */
    private function failedResult(
        array $request_payload,
        ?int $http_status,
        string $error_message,
        ?array $response_payload = null
    ): array {
        return [
            'success' => false,
            'http_status' => $http_status,
            'request_payload' => $request_payload,
            'response_payload' => $response_payload,
            'error_message' => $error_message,
            'servo_reference' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $response_payload
     */
    private function extractServoReference(array $response_payload): ?string
    {
        $reference = data_get($response_payload, 'data.id')
            ?? data_get($response_payload, 'data.order_id')
            ?? data_get($response_payload, 'data.reference')
            ?? data_get($response_payload, 'data.invoice_no');

        if ($reference === null || $reference === '') {
            return null;
        }

        return is_scalar($reference) ? (string) $reference : null;
    }
}
