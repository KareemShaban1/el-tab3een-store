<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Product;
use App\ProductReview;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class StoreProductReviewController extends Controller
{
    public function store(Request $request, int $id)
    {
        $business_id = StorefrontController::resolveBusinessId($request);
        $contact = auth('customer')->user();

        if (! $contact || (int) $contact->business_id !== $business_id) {
            abort(403);
        }

        $product = Product::query()
            ->where('business_id', $business_id)
            ->active()
            ->productForSales()
            ->activeInApp()
            ->findOrFail($id);

        $request->merge([
            'rating' => (int) $request->input('rating'),
            'comment' => trim(strip_tags((string) $request->input('comment', ''))),
        ]);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3|max:2000',
        ], [
            'rating.required' => __('storefront.reviews.rating_required'),
            'rating.integer' => __('storefront.reviews.rating_required'),
            'rating.min' => __('storefront.reviews.rating_required'),
            'rating.max' => __('storefront.reviews.rating_required'),
            'comment.required' => __('storefront.reviews.comment_required'),
            'comment.min' => __('storefront.reviews.comment_min'),
            'comment.max' => __('storefront.reviews.comment_max'),
        ]);

        $keys = [
            'business_id' => $business_id,
            'product_id' => $product->id,
            'contact_id' => $contact->id,
        ];

        $existing = ProductReview::query()->where($keys)->first();

        try {
            if ($existing) {
                $existing->fill([
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                ])->save();
                $review = $existing->fresh();
                $message = $review->is_visible
                    ? __('storefront.reviews.updated')
                    : __('storefront.reviews.updated_hidden');
            } else {
                $review = ProductReview::query()->create($keys + [
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                    'is_visible' => true,
                ]);
                $message = __('storefront.reviews.saved');
            }
        } catch (QueryException $e) {
            $review = ProductReview::query()->where($keys)->first();
            if (! $review) {
                throw $e;
            }

            $review->fill([
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ])->save();
            $message = $review->is_visible
                ? __('storefront.reviews.updated')
                : __('storefront.reviews.updated_hidden');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'msg' => $message,
                'data' => [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'is_visible' => (bool) $review->is_visible,
                ],
            ]);
        }

        return redirect()
            ->to(route('store.products.show', $product->id).'#product-reviews')
            ->with('review_status', $message);
    }
};
