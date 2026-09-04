<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\BusinessLocation;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\MfgPackagingProfile;
use Modules\Manufacturing\Support\PackagingFeature;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Modules\Manufacturing\Utils\PackagingUtil;
use Yajra\DataTables\Facades\DataTables;

class PackagingProductionController extends Controller
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
            $productions = Transaction::join('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
                ->join('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
                ->join('variations as v', 'v.id', '=', 'pl.variation_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('mfg_packaging_profiles as mpp', 'transactions.mfg_packaging_profile_id', '=', 'mpp.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'production_purchase')
                ->where('transactions.mfg_stage', 'packaging')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'bl.name as location_name',
                    'mpp.name as profile_name',
                    DB::raw('IF(p.type="variable",
                            CONCAT(p.name, " - ", pv.name, " - ", v.name, " (", v.sub_sku, ")"),
                            CONCAT(p.name, " (", v.sub_sku, ")")
                            ) as product_name'),
                    'pl.quantity',
                    'final_total',
                    'u.short_name as unit_name',
                    'mfg_containers_count',
                    'mfg_cartons_count',
                    'mfg_container_type',
                    'mfg_is_final'
                )->groupBy('transactions.id');

            return Datatables::of($productions)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="' . action([self::class, 'show'], $row->id) . '" class="btn btn-info btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __('messages.view') . '</button>';
                    if ($row->mfg_is_final == 0) {
                        $html .= ' <button data-href="' . action([self::class, 'destroy'], [$row->id]) . '" class="delete-packaging-production btn btn-xs btn-danger"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</button>';
                    }
                    return $html;
                })
                ->editColumn('final_total', '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>')
                ->editColumn('quantity', function ($row) {
                    return "<span class='display_currency' data-currency_symbol='false' data-orig-value='{$row->quantity}'>{$row->quantity}</span> {$row->unit_name}";
                })
                ->editColumn('mfg_containers_count', function ($row) {
                    $label = $row->mfg_container_type == 'bag' ? __('manufacturing::lang.bags') : __('manufacturing::lang.bottles');
                    return $row->mfg_containers_count . ' ' . $label;
                })
                ->editColumn('mfg_cartons_count', function ($row) {
                    return $row->mfg_cartons_count . ' ' . __('manufacturing::lang.cartons');
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'quantity'])
                ->make(true);
        }

        return view('manufacturing::packaging_production.index');
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        $business_locations = BusinessLocation::forDropdown($business_id);
        $profile_dropdown = MfgPackagingProfile::forDropdown($business_id);

        return view('manufacturing::packaging_production.create')
            ->with(compact('business_locations', 'profile_dropdown'));
    }

    public function getProfileDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        $profile_id = $request->input('profile_id');
        $location_id = $request->input('location_id');
        $containers_count = $request->input('containers_count');

        $profile = MfgPackagingProfile::where('business_id', $business_id)
            ->with(['materials'])
            ->findOrFail($profile_id);

        $details = $this->packagingUtil->getProfileDetailsForLocation($profile, $location_id, $containers_count);

        return view('manufacturing::packaging_production.profile_details')
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
                'containers_count' => 'required|numeric|min:1',
                'final_total' => 'required',
            ]);

            $profile = MfgPackagingProfile::where('business_id', $business_id)
                ->with('materials')
                ->findOrFail($request->input('packaging_profile_id'));

            $containers_count = $this->productUtil->num_uf($request->input('containers_count'));
            $validation_errors = $this->packagingUtil->validatePackagingInput($profile, $containers_count);

            if (! empty($validation_errors)) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => implode(' ', $validation_errors),
                ]);
            }

            $shortages = $this->packagingUtil->checkStockAvailability($profile, $request->input('location_id'), $containers_count);
            $is_final = ! empty($request->input('finalize')) ? 1 : 0;

            if ($is_final && ! empty($shortages)) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => __('manufacturing::lang.insufficient_stock_for_packaging'),
                ]);
            }

            $calc = $this->packagingUtil->calculatePackaging($profile, $containers_count);
            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
            $user_id = $request->session()->get('user.id');

            $transaction_data = $request->only(['ref_no', 'transaction_date', 'location_id', 'final_total']);
            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'production_purchase';
            $transaction_data['status'] = $is_final ? 'received' : 'pending';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total']);
            $transaction_data['mfg_is_final'] = $is_final;
            $transaction_data['mfg_stage'] = 'packaging';
            $transaction_data['mfg_packaging_profile_id'] = $profile->id;
            $transaction_data['mfg_containers_count'] = (int) $containers_count;
            $transaction_data['mfg_cartons_count'] = $calc['full_cartons'];
            $transaction_data['mfg_container_type'] = $profile->container_type;

            if (! empty($request->input('mfg_parent_production_purchase_id'))) {
                $transaction_data['mfg_parent_production_purchase_id'] = $request->input('mfg_parent_production_purchase_id');
            }

            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            if (empty($transaction_data['ref_no'])) {
                $prefix = ! empty($manufacturing_settings['ref_no_prefix']) ? $manufacturing_settings['ref_no_prefix'] : null;
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count, null, $prefix);
            }

            $output_variation = Variation::where('id', $profile->output_variation_id)->with('product')->first();
            $carton_qty = $calc['full_cartons'];
            $final_total_uf = $transaction_data['final_total'];
            $unit_purchase_line_total = $carton_qty > 0 ? $final_total_uf / $carton_qty : 0;
            $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

            $purchase_line_data = [
                'variation_id' => $profile->output_variation_id,
                'quantity' => $this->productUtil->num_f($carton_qty),
                'product_id' => $output_variation->product_id,
                'product_unit_id' => $output_variation->product->unit_id,
                'pp_without_discount' => $unit_purchase_line_total_f,
                'discount_percent' => 0,
                'purchase_price' => $unit_purchase_line_total_f,
                'purchase_price_inc_tax' => $unit_purchase_line_total_f,
                'item_tax' => 0,
                'purchase_line_tax_id' => null,
            ];

            DB::beginTransaction();

            $transaction = Transaction::create($transaction_data);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            $update_product_price = ! empty($manufacturing_settings['enable_updating_product_price']) && $is_final;

            $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);
            $this->productUtil->adjustStockOverSelling($transaction);

            $bulk_variation = Variation::where('id', $profile->bulk_variation_id)->with('product')->first();
            $bulk_unit_price = $bulk_variation->dpp_inc_tax;

            $sell_lines = [[
                'product_id' => $bulk_variation->product_id,
                'variation_id' => $bulk_variation->id,
                'quantity' => $this->productUtil->num_f($calc['bulk_consumed']),
                'item_tax' => 0,
                'tax_id' => null,
                'unit_price' => $bulk_unit_price,
                'unit_price_inc_tax' => $bulk_unit_price,
                'enable_stock' => $bulk_variation->product->enable_stock,
                'product_unit_id' => $bulk_variation->product->unit_id,
            ]];

            foreach ($calc['materials'] as $material) {
                if ($material['quantity'] <= 0) {
                    continue;
                }
                $material_variation = Variation::with('product')->find($material['variation_id']);
                $sell_lines[] = [
                    'product_id' => $material['product_id'],
                    'variation_id' => $material['variation_id'],
                    'quantity' => $this->productUtil->num_f($material['quantity']),
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price' => $material['unit_price'],
                    'unit_price_inc_tax' => $material['unit_price'],
                    'enable_stock' => $material['enable_stock'],
                    'product_unit_id' => $material_variation->product->unit_id,
                ];
            }

            $transaction_sell_data = [
                'business_id' => $business_id,
                'location_id' => $transaction->location_id,
                'transaction_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'status' => $is_final ? 'final' : 'draft',
                'type' => 'production_sell',
                'mfg_parent_production_purchase_id' => $transaction->id,
                'payment_status' => 'due',
                'final_total' => $transaction->final_total,
            ];

            $production_sell = Transaction::create($transaction_sell_data);
            $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction_sell_data['location_id']);

            if ($production_sell->status == 'final') {
                foreach ($sell_lines as $sell_line) {
                    if (! empty($sell_line['enable_stock'])) {
                        $this->productUtil->decreaseProductQuantity(
                            $sell_line['product_id'],
                            $sell_line['variation_id'],
                            $production_sell->location_id,
                            $this->productUtil->num_uf($sell_line['quantity'])
                        );
                    }
                }

                $business_details = $this->businessUtil->getDetails($business_id);
                $pos_settings = empty($business_details->pos_settings)
                    ? $this->businessUtil->defaultPosSettings()
                    : json_decode($business_details->pos_settings, true);

                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $production_sell->location_id,
                    'pos_settings' => $pos_settings,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
            }

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.added_success')];
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

        $production = Transaction::where('business_id', $business_id)
            ->where('type', 'production_purchase')
            ->where('mfg_stage', 'packaging')
            ->with(['purchase_lines', 'purchase_lines.variations', 'location'])
            ->findOrFail($id);

        $production_sell = Transaction::where('business_id', $business_id)
            ->where('type', 'production_sell')
            ->where('mfg_parent_production_purchase_id', $id)
            ->with(['sell_lines', 'sell_lines.variations'])
            ->first();

        $profile = MfgPackagingProfile::with(['bulkVariation', 'outputVariation'])->find($production->mfg_packaging_profile_id);

        return view('manufacturing::packaging_production.show')
            ->with(compact('production', 'production_sell', 'profile'));
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        try {
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'production_purchase')
                ->where('mfg_stage', 'packaging')
                ->where('mfg_is_final', 0)
                ->findOrFail($id);

            Transaction::where('mfg_parent_production_purchase_id', $id)->delete();
            $transaction->delete();

            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}