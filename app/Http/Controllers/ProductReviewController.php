<?php

namespace App\Http\Controllers;

use App\ProductReview;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductReviewController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin($user, $business_id);

        if (! $is_admin && ! $user->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (! is_storefront_business($business_id)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $reviews = ProductReview::query()
                ->where('product_reviews.business_id', $business_id)
                ->leftJoin('products', 'products.id', '=', 'product_reviews.product_id')
                ->leftJoin('contacts', 'contacts.id', '=', 'product_reviews.contact_id')
                ->select([
                    'product_reviews.id',
                    'product_reviews.created_at',
                    'product_reviews.rating',
                    'product_reviews.comment',
                    'product_reviews.is_visible',
                    'product_reviews.product_id',
                    'products.name as product_name',
                    'contacts.name as customer_name',
                    'contacts.mobile as customer_mobile',
                ]);

            $visibility = (string) $request->input('visibility', '');
            if ($visibility === 'visible') {
                $reviews->where('product_reviews.is_visible', true);
            } elseif ($visibility === 'hidden') {
                $reviews->where('product_reviews.is_visible', false);
            }

            $rating = (int) $request->input('rating', 0);
            if ($rating >= 1 && $rating <= 5) {
                $reviews->where('product_reviews.rating', $rating);
            }

            $search = trim((string) $request->input('q', ''));
            if ($search !== '') {
                $reviews->where(function ($query) use ($search) {
                    $like = '%'.$search.'%';
                    $query->where('products.name', 'like', $like)
                        ->orWhere('contacts.name', 'like', $like)
                        ->orWhere('contacts.mobile', 'like', $like)
                        ->orWhere('product_reviews.comment', 'like', $like);
                });
            }

            return DataTables::of($reviews)
                ->editColumn('created_at', function ($row) {
                    return optional($row->created_at)->format('Y-m-d H:i') ?: '';
                })
                ->editColumn('product_name', function ($row) {
                    $name = $row->product_name ?: ('#'.$row->product_id);
                    $url = route('store.products.show', $row->product_id);

                    return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($name).'</a>';
                })
                ->addColumn('customer', function ($row) {
                    $name = e($row->customer_name ?: __('product_reviews.customer'));
                    $mobile = $row->customer_mobile
                        ? '<div class="text-muted">'.e($row->customer_mobile).'</div>'
                        : '';

                    return $name.$mobile;
                })
                ->editColumn('rating', function ($row) {
                    return $this->starsHtml((int) $row->rating);
                })
                ->editColumn('comment', function ($row) {
                    $full = e($row->comment);
                    $short = e(\Illuminate\Support\Str::limit((string) $row->comment, 140));

                    return '<span title="'.$full.'">'.$short.'</span>';
                })
                ->editColumn('is_visible', function ($row) {
                    if ($row->is_visible) {
                        return '<span class="label label-success">'.e(__('product_reviews.visible')).'</span>';
                    }

                    return '<span class="label label-default">'.e(__('product_reviews.hidden')).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $toggleLabel = $row->is_visible
                        ? __('product_reviews.hide')
                        : __('product_reviews.show');
                    $toggleClass = $row->is_visible ? 'tw-dw-btn-warning' : 'tw-dw-btn-success';
                    $toggleUrl = route('product-reviews.visibility', $row->id);
                    $deleteUrl = route('product-reviews.destroy', $row->id);

                    return '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline '.$toggleClass.' toggle_product_review" data-href="'.e($toggleUrl).'">'.e($toggleLabel).'</button>'
                        .' <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete_product_review" data-href="'.e($deleteUrl).'"><i class="glyphicon glyphicon-trash"></i> '.e(__('messages.delete')).'</button>';
                })
                ->rawColumns(['product_name', 'customer', 'rating', 'comment', 'is_visible', 'action'])
                ->make(true);
        }

        $statsRow = ProductReview::query()
            ->where('business_id', $business_id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) as visible_count')
            ->selectRaw('SUM(CASE WHEN is_visible = 0 THEN 1 ELSE 0 END) as hidden_count')
            ->first();

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'visible' => (int) ($statsRow->visible_count ?? 0),
            'hidden' => (int) ($statsRow->hidden_count ?? 0),
        ];

        return view('product_review.index', compact('stats'));
    }

    public function toggleVisibility($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $review = ProductReview::query()
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $review->is_visible = ! $review->is_visible;
        $review->save();

        return [
            'success' => true,
            'msg' => $review->is_visible
                ? __('product_reviews.shown_success')
                : __('product_reviews.hidden_success'),
        ];
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $review = ProductReview::query()
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $review->delete();

        return [
            'success' => true,
            'msg' => __('product_reviews.deleted_success'),
        ];
    }

    private function starsHtml(int $rating): string
    {
        $rating = max(0, min(5, $rating));
        $html = '<span class="product-review-stars" title="'.e($rating).'/5">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<span style="color:'.($i <= $rating ? '#f59e0b' : '#d1d5db').'">★</span>';
        }
        $html .= '</span>';

        return $html;
    }
};
