<?php

namespace App\Http\Controllers;

use App\Discount;
use App\PromoCode;
use App\Services\Pricing\PromoCodeService;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PromoCodeController extends Controller
{
    protected $commonUtil;

    protected $promoCodeService;

    public function __construct(Util $commonUtil, PromoCodeService $promoCodeService)
    {
        $this->commonUtil = $commonUtil;
        $this->promoCodeService = $promoCodeService;
    }

    public function index()
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $codes = PromoCode::where('promo_codes.business_id', $business_id)
                ->leftJoin('discounts as d', 'promo_codes.discount_id', '=', 'd.id')
                ->select([
                    'promo_codes.id',
                    'promo_codes.code',
                    'promo_codes.description',
                    'promo_codes.is_active',
                    'promo_codes.starts_at',
                    'promo_codes.ends_at',
                    'promo_codes.max_uses_total',
                    'promo_codes.used_count',
                    'promo_codes.is_single_use',
                    'd.name as discount_name',
                    'd.discount_amount',
                    'd.discount_type',
                    'd.promo_application',
                ]);

            return DataTables::of($codes)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="'.action([\App\Http\Controllers\PromoCodeController::class, 'edit'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".promo_code_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>&nbsp;';
                    $html .= '<button data-href="'.action([\App\Http\Controllers\PromoCodeController::class, 'destroy'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_promo_code_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';

                    return $html;
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->editColumn('discount_amount', function ($row) {
                    $suffix = $row->discount_type == 'percentage' ? ' %' : '';

                    return number_format((float) $row->discount_amount, 2).$suffix;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('promo_code.index');
    }

    public function create()
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $discounts = Discount::where('business_id', $business_id)
            ->where('requires_promo_code', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('promo_code.create')->with(compact('discounts'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $code = PromoCode::normalizeCode($request->input('code'));

            $exists = PromoCode::where('business_id', $business_id)->where('code', $code)->exists();
            if ($exists) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_exists')];
            }

            $input = [
                'business_id' => $business_id,
                'discount_id' => $request->input('discount_id'),
                'code' => $code,
                'description' => $request->input('description'),
                'is_active' => $request->has('is_active') ? 1 : 0,
                'starts_at' => $request->filled('starts_at') ? $this->commonUtil->uf_date($request->input('starts_at'), true) : null,
                'ends_at' => $request->filled('ends_at') ? $this->commonUtil->uf_date($request->input('ends_at'), true) : null,
                'max_uses_total' => $request->input('max_uses_total') !== null && $request->input('max_uses_total') !== '' ? $request->input('max_uses_total') : null,
                'max_uses_per_customer' => $request->input('max_uses_per_customer') !== null && $request->input('max_uses_per_customer') !== '' ? $request->input('max_uses_per_customer') : null,
                'is_single_use' => $request->has('is_single_use') ? 1 : 0,
                'restore_on_return' => $request->has('restore_on_return') ? 1 : 0,
                'created_by' => $request->session()->get('user.id'),
            ];

            if ($input['is_single_use']) {
                $input['max_uses_total'] = 1;
            }

            PromoCode::create($input);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function edit($id)
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $promo_code = PromoCode::where('business_id', $business_id)->findOrFail($id);
        $discounts = Discount::where('business_id', $business_id)
            ->where('requires_promo_code', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        $starts_at = ! empty($promo_code->starts_at) ? $this->commonUtil->format_date($promo_code->starts_at->toDateTimeString(), true) : null;
        $ends_at = ! empty($promo_code->ends_at) ? $this->commonUtil->format_date($promo_code->ends_at->toDateTimeString(), true) : null;

        return view('promo_code.edit')->with(compact('promo_code', 'discounts', 'starts_at', 'ends_at'));
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $promo_code = PromoCode::where('business_id', $business_id)->findOrFail($id);
            $code = PromoCode::normalizeCode($request->input('code'));

            $exists = PromoCode::where('business_id', $business_id)
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return ['success' => false, 'msg' => __('lang_v1.promo_code_exists')];
            }

            $promo_code->discount_id = $request->input('discount_id');
            $promo_code->code = $code;
            $promo_code->description = $request->input('description');
            $promo_code->is_active = $request->has('is_active') ? 1 : 0;
            $promo_code->starts_at = $request->filled('starts_at') ? $this->commonUtil->uf_date($request->input('starts_at'), true) : null;
            $promo_code->ends_at = $request->filled('ends_at') ? $this->commonUtil->uf_date($request->input('ends_at'), true) : null;
            $promo_code->max_uses_total = $request->input('max_uses_total') !== null && $request->input('max_uses_total') !== '' ? $request->input('max_uses_total') : null;
            $promo_code->max_uses_per_customer = $request->input('max_uses_per_customer') !== null && $request->input('max_uses_per_customer') !== '' ? $request->input('max_uses_per_customer') : null;
            $promo_code->is_single_use = $request->has('is_single_use') ? 1 : 0;
            $promo_code->restore_on_return = $request->has('restore_on_return') ? 1 : 0;
            if ($promo_code->is_single_use) {
                $promo_code->max_uses_total = 1;
            }
            $promo_code->save();

            return ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('discount.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $promo_code = PromoCode::where('business_id', $business_id)->findOrFail($id);
            $promo_code->delete();

            return ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Validate promo code for POS / AJAX.
     */
    public function validateCode(Request $request)
    {
        if (! auth()->user()->can('discount.access') && ! auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $result = $this->promoCodeService->validate($request->input('code', ''), [
            'business_id' => $business_id,
            'location_id' => $request->input('location_id'),
            'contact_id' => $request->input('contact_id'),
            'customer_group_id' => $request->input('customer_group_id'),
            'channel' => $request->input('channel', 'pos'),
            'cart_subtotal' => (float) $request->input('cart_subtotal', 0),
            'cart_lines' => $request->input('cart_lines', []),
        ]);

        if (! empty($result['success'])) {
            $result['msg'] = __('lang_v1.promo_code_applied');
            // Don't serialize full models in JSON unexpectedly
            unset($result['promo_code'], $result['discount']);
            $result['invoice_discount'] = $result['invoice_discount'] ?? null;
        }

        return response()->json($result);
    }
}
