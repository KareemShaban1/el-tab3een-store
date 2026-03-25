<?php

namespace App\Http\Controllers\Frontend;

use App\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StoreAccountController extends Controller
{
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
            'country' => 'nullable|string|max:120',
            'zip_code' => 'nullable|string|max:20',
        ]);

        $customer->fill($validated);
        $customer->save();

        if (! $request->expectsJson()) {
            return back()->with('status', [
                'success' => true,
                'msg' => 'Profile updated.',
            ]);
        }

        return $this->respond([
            'success' => true,
            'msg' => 'Profile updated.',
        ]);
    }

    public function orders(Request $request)
    {
        $customer = auth('customer')->user();

        $orders = Transaction::where('business_id', $customer->business_id)
            ->where('contact_id', $customer->id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->select('id', 'invoice_no', 'transaction_date', 'final_total', 'status', 'sub_status', 'ecommerce_order_status', 'payment_status', 'shipping_status')
            ->orderByDesc('id')
            ->paginate(20);

        if (! $request->expectsJson()) {
            return view('frontend.store.account.orders')->with('orders', $orders);
        }

        return $this->respond([
            'success' => true,
            'data' => $orders->items(),
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
        $customer = auth('customer')->user();

        $order = Transaction::where('business_id', $customer->business_id)
            ->where('contact_id', $customer->id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->with(['sell_lines', 'payment_lines'])
            ->findOrFail($id);

        if (! $request->expectsJson()) {
            return view('frontend.store.account.order_show')->with('order', $order);
        }

        return $this->respond([
            'success' => true,
            'data' => $order,
        ]);
    }
}

