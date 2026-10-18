<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\BusinessLocation;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\MfgPackagingProfile;
use Modules\Manufacturing\Entities\MfgPackagingTransformation;
use Modules\Manufacturing\Support\PackagingFeature;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Modules\Manufacturing\Utils\PackagingUtil;
use Yajra\DataTables\Facades\DataTables;

class PackagingTransformationController extends Controller
{
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $mfgUtil;
    protected $packagingUtil;
    protected $businessUtil;

    public function __construct(
        ModuleUtil $moduleUtil,
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        ManufacturingUtil $mfgUtil,
        PackagingUtil $packagingUtil,
        BusinessUtil $businessUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->mfgUtil = $mfgUtil;
        $this->packagingUtil = $packagingUtil;
        $this->businessUtil = $businessUtil;
    }

    protected function authorizePackaging($business_id)
    {
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! PackagingFeature::isEnabledForBusiness($business_id)) {
            abort(404);
        }

        if (! PackagingFeature::userCanAccessPackaging()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        if (request()->ajax()) {
            $rows = MfgPackagingTransformation::join('business_locations as bl', 'bl.id', '=', 'mfg_packaging_transformations.location_id')
                ->join('mfg_packaging_profiles as mpp', 'mpp.id', '=', 'mfg_packaging_transformations.packaging_profile_id')
                ->join('variations as cv', 'cv.id', '=', 'mfg_packaging_transformations.carton_variation_id')
                ->join('product_variations as cpv', 'cpv.id', '=', 'cv.product_variation_id')
                ->join('products as cp', 'cp.id', '=', 'cv.product_id')
                ->join('variations as uv', 'uv.id', '=', 'mfg_packaging_transformations.unit_variation_id')
                ->join('product_variations as upv', 'upv.id', '=', 'uv.product_variation_id')
                ->join('products as up', 'up.id', '=', 'uv.product_id')
                ->where('mfg_packaging_transformations.business_id', $business_id)
                ->select(
                    'mfg_packaging_transformations.id',
                    'mfg_packaging_transformations.ref_no',
                    'mfg_packaging_transformations.transformed_at',
                    'mfg_packaging_transformations.carton_qty',
                    'mfg_packaging_transformations.unit_qty',
                    'mfg_packaging_transformations.units_per_carton',
                    'bl.name as location_name',
                    'mpp.name as profile_name',
                    DB::raw('IF(cp.type="variable",
                            CONCAT(cp.name, " - ", cpv.name, " - ", cv.name, " (", cv.sub_sku, ")"),
                            CONCAT(cp.name, " (", cv.sub_sku, ")")
                            ) as carton_name'),
                    DB::raw('IF(up.type="variable",
                            CONCAT(up.name, " - ", upv.name, " - ", uv.name, " (", uv.sub_sku, ")"),
                            CONCAT(up.name, " (", uv.sub_sku, ")")
                            ) as unit_name')
                );

            return Datatables::of($rows)
                ->editColumn('transformed_at', '{{@format_datetime($transformed_at)}}')
                ->editColumn('carton_qty', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="false" data-orig-value="' . $row->carton_qty . '">' . $row->carton_qty . '</span>';
                })
                ->editColumn('unit_qty', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="false" data-orig-value="' . $row->unit_qty . '">' . $row->unit_qty . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="' . action([self::class, 'show'], $row->id) . '" class="btn btn-info btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __('messages.view') . '</button>';
                })
                ->rawColumns(['carton_qty', 'unit_qty', 'action'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $profile_dropdown = MfgPackagingProfile::forTransformDropdown($business_id);

        return view('manufacturing::packaging_transformation.index')
            ->with(compact('business_locations', 'profile_dropdown'));
    }

    public function getProfileDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        $profile = MfgPackagingProfile::where('business_id', $business_id)
            ->findOrFail($request->input('profile_id'));

        if (! $profile->canTransformCartonToUnit()) {
            return '<div class="alert alert-warning">' . __('manufacturing::lang.profile_not_ready_for_transformation') . '</div>';
        }

        $details = $this->packagingUtil->getTransformationDetailsForLocation(
            $profile,
            $request->input('location_id'),
            $request->input('carton_qty')
        );

        return view('manufacturing::packaging_transformation.profile_details')
            ->with(compact('details'))
            ->render();
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        try {
            $request->validate([
                'transaction_date' => 'required',
                'location_id' => 'required',
                'packaging_profile_id' => 'required|integer',
                'carton_qty' => 'required|numeric|min:1',
            ]);

            $profile = MfgPackagingProfile::where('business_id', $business_id)
                ->with(['outputVariation.product', 'unitVariation.product'])
                ->findOrFail($request->input('packaging_profile_id'));

            $carton_qty = $this->productUtil->num_uf($request->input('carton_qty'));
            $validation_errors = $this->packagingUtil->validateTransformationInput($profile, $carton_qty);

            if (! empty($validation_errors)) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => implode(' ', $validation_errors),
                ]);
            }

            $location_id = $request->input('location_id');
            $carton_available = $this->packagingUtil->getAvailableStock($profile->output_variation_id, $location_id);

            if ($carton_available < $carton_qty) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => __('manufacturing::lang.insufficient_carton_stock_for_transformation'),
                ]);
            }

            $calc = $this->packagingUtil->calculateTransformation($profile, $carton_qty);
            $user_id = $request->session()->get('user.id');
            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
            $transformed_at = $this->productUtil->uf_date($request->input('transaction_date'), true);

            $carton_variation = $profile->outputVariation;
            $unit_variation = $profile->unitVariation;
            $unit_qty = $calc['unit_qty'];

            // Transfer carton purchase value into unit stock.
            $carton_unit_cost = (float) $carton_variation->dpp_inc_tax;
            $total_cost = $carton_unit_cost * $carton_qty;
            $unit_purchase_price = $unit_qty > 0 ? $total_cost / $unit_qty : 0;
            $unit_purchase_price_f = $this->productUtil->num_f($unit_purchase_price);

            DB::beginTransaction();

            $ref_count = $this->productUtil->setAndGetReferenceCount('production_purchase');
            $prefix = ! empty($manufacturing_settings['ref_no_prefix']) ? $manufacturing_settings['ref_no_prefix'] : null;
            $ref_no = ! empty($request->input('ref_no'))
                ? $request->input('ref_no')
                : $this->productUtil->generateReferenceNumber('production_purchase', $ref_count, null, $prefix);

            $purchase_transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'production_purchase',
                'status' => 'received',
                'payment_status' => 'due',
                'transaction_date' => $transformed_at,
                'created_by' => $user_id,
                'ref_no' => $ref_no,
                'final_total' => $total_cost,
                'mfg_is_final' => 1,
                'mfg_stage' => 'packaging_transform',
                'mfg_packaging_profile_id' => $profile->id,
                'mfg_containers_count' => (int) $unit_qty,
                'mfg_cartons_count' => (int) $carton_qty,
                'mfg_container_type' => $profile->container_type,
            ]);

            $purchase_line_data = [
                'variation_id' => $unit_variation->id,
                'quantity' => $this->productUtil->num_f($unit_qty),
                'product_id' => $unit_variation->product_id,
                'product_unit_id' => $unit_variation->product->unit_id,
                'pp_without_discount' => $unit_purchase_price_f,
                'discount_percent' => 0,
                'purchase_price' => $unit_purchase_price_f,
                'purchase_price_inc_tax' => $unit_purchase_price_f,
                'item_tax' => 0,
                'purchase_line_tax_id' => null,
            ];

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            $this->productUtil->createOrUpdatePurchaseLines($purchase_transaction, [$purchase_line_data], $currency_details, false);
            $this->productUtil->adjustStockOverSelling($purchase_transaction);

            $sell_transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'transaction_date' => $transformed_at,
                'created_by' => $user_id,
                'status' => 'final',
                'type' => 'production_sell',
                'mfg_parent_production_purchase_id' => $purchase_transaction->id,
                'payment_status' => 'due',
                'final_total' => $total_cost,
                'mfg_stage' => 'packaging_transform',
                'mfg_packaging_profile_id' => $profile->id,
            ]);

            $sell_lines = [[
                'product_id' => $carton_variation->product_id,
                'variation_id' => $carton_variation->id,
                'quantity' => $this->productUtil->num_f($carton_qty),
                'item_tax' => 0,
                'tax_id' => null,
                'unit_price' => $carton_unit_cost,
                'unit_price_inc_tax' => $carton_unit_cost,
                'enable_stock' => $carton_variation->product->enable_stock,
                'product_unit_id' => $carton_variation->product->unit_id,
            ]];

            $this->transactionUtil->createOrUpdateSellLines($sell_transaction, $sell_lines, $location_id);
            $sell_transaction->load('sell_lines');

            if (! empty($carton_variation->product->enable_stock)) {
                $this->productUtil->decreaseProductQuantity(
                    $carton_variation->product_id,
                    $carton_variation->id,
                    $location_id,
                    $carton_qty
                );
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings)
                ? $this->businessUtil->defaultPosSettings()
                : json_decode($business_details->pos_settings, true);

            $business = [
                'id' => $business_id,
                'accounting_method' => $request->session()->get('business.accounting_method'),
                'location_id' => $location_id,
                'pos_settings' => $pos_settings,
            ];
            $this->transactionUtil->mapPurchaseSell($business, $sell_transaction->sell_lines, 'production_purchase');

            MfgPackagingTransformation::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'packaging_profile_id' => $profile->id,
                'carton_variation_id' => $carton_variation->id,
                'unit_variation_id' => $unit_variation->id,
                'carton_qty' => $carton_qty,
                'unit_qty' => $unit_qty,
                'units_per_carton' => $calc['units_per_carton'],
                'ref_no' => $ref_no,
                'transformed_at' => $transformed_at,
                'created_by' => $user_id,
                'production_purchase_id' => $purchase_transaction->id,
                'production_sell_id' => $sell_transaction->id,
                'notes' => $request->input('notes'),
            ]);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('manufacturing::lang.transformation_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([self::class, 'index'])->with('status', $output);
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        $transformation = MfgPackagingTransformation::where('business_id', $business_id)
            ->with([
                'profile',
                'location',
                'cartonVariation.product',
                'cartonVariation.product_variation',
                'unitVariation.product',
                'unitVariation.product_variation',
                'createdBy',
            ])
            ->findOrFail($id);

        return view('manufacturing::packaging_transformation.show')
            ->with(compact('transformation'));
    }
}
