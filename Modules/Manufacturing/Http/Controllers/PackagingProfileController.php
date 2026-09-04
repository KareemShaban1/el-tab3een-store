<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Variation;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\MfgPackagingMaterial;
use Modules\Manufacturing\Entities\MfgPackagingProfile;
use Modules\Manufacturing\Support\PackagingFeature;
use Yajra\DataTables\Facades\DataTables;

class PackagingProfileController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    protected function authorizePackaging($business_id, $manage = false)
    {
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! PackagingFeature::isEnabledForBusiness($business_id)) {
            abort(404);
        }

        if ($manage) {
            if (! PackagingFeature::userCanManageProfiles()) {
                abort(403, 'Unauthorized action.');
            }
        } elseif (! PackagingFeature::userCanAccessPackaging()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id);

        if (request()->ajax()) {
            $profiles = MfgPackagingProfile::where('business_id', $business_id)
                ->with(['bulkVariation.product', 'bulkVariation.product_variation', 'outputVariation.product', 'outputVariation.product_variation']);

            return Datatables::of($profiles)
                ->addColumn('bulk_product', function ($row) {
                    return MfgPackagingProfile::variationLabel($row->bulkVariation);
                })
                ->addColumn('output_product', function ($row) {
                    return MfgPackagingProfile::variationLabel($row->outputVariation);
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label bg-green">' . __('manufacturing::lang.active') . '</span>'
                        : '<span class="label bg-red">' . __('manufacturing::lang.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="' . action([self::class, 'show'], [$row->id]) . '" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a> ';
                    if (PackagingFeature::userCanManageProfiles()) {
                        $html .= '<a href="' . action([self::class, 'edit'], [$row->id]) . '" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</a> ';
                        $html .= '<button data-href="' . action([self::class, 'destroy'], [$row->id]) . '" class="btn btn-xs btn-danger delete-packaging-profile"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</button>';
                    }
                    return $html;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('manufacturing::packaging_profile.index');
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        return view('manufacturing::packaging_profile.create');
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        try {
            $request->validate([
                'name' => 'required',
                'bulk_variation_id' => 'required|integer',
                'output_variation_id' => 'required|integer|different:bulk_variation_id',
                'container_type' => 'required|in:bottle,bag',
                'units_per_carton' => 'required|integer|min:1',
                'bulk_qty_per_container' => 'required|numeric|min:0.0001',
            ]);

            DB::beginTransaction();

            $profile = MfgPackagingProfile::create([
                'business_id' => $business_id,
                'name' => $request->input('name'),
                'bulk_variation_id' => $request->input('bulk_variation_id'),
                'output_variation_id' => $request->input('output_variation_id'),
                'container_type' => $request->input('container_type'),
                'container_volume' => $request->input('container_volume'),
                'units_per_carton' => $request->input('units_per_carton'),
                'bulk_qty_per_container' => $request->input('bulk_qty_per_container'),
                'waste_percent' => $request->input('waste_percent', 0),
                'is_active' => ! empty($request->input('is_active')) ? 1 : 0,
                'instructions' => $request->input('instructions'),
            ]);

            $this->syncMaterials($profile, $request->input('materials', []));

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

        $profile = MfgPackagingProfile::where('business_id', $business_id)
            ->with(['materials.variation.product', 'materials.variation.product_variation', 'bulkVariation.product', 'outputVariation.product'])
            ->findOrFail($id);

        return view('manufacturing::packaging_profile.show')->with(compact('profile'));
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        $profile = MfgPackagingProfile::where('business_id', $business_id)
            ->with(['materials.variation.product', 'materials.variation.product_variation', 'bulkVariation.product', 'bulkVariation.product_variation', 'outputVariation.product', 'outputVariation.product_variation'])
            ->findOrFail($id);

        return view('manufacturing::packaging_profile.edit')->with(compact('profile'));
    }

    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        try {
            $request->validate([
                'name' => 'required',
                'bulk_variation_id' => 'required|integer',
                'output_variation_id' => 'required|integer|different:bulk_variation_id',
                'container_type' => 'required|in:bottle,bag',
                'units_per_carton' => 'required|integer|min:1',
                'bulk_qty_per_container' => 'required|numeric|min:0.0001',
            ]);

            $profile = MfgPackagingProfile::where('business_id', $business_id)->findOrFail($id);

            DB::beginTransaction();

            $profile->update([
                'name' => $request->input('name'),
                'bulk_variation_id' => $request->input('bulk_variation_id'),
                'output_variation_id' => $request->input('output_variation_id'),
                'container_type' => $request->input('container_type'),
                'container_volume' => $request->input('container_volume'),
                'units_per_carton' => $request->input('units_per_carton'),
                'bulk_qty_per_container' => $request->input('bulk_qty_per_container'),
                'waste_percent' => $request->input('waste_percent', 0),
                'is_active' => ! empty($request->input('is_active')) ? 1 : 0,
                'instructions' => $request->input('instructions'),
            ]);

            MfgPackagingMaterial::where('packaging_profile_id', $profile->id)->delete();
            $this->syncMaterials($profile, $request->input('materials', []));

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([self::class, 'index'])->with('status', $output);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        try {
            $profile = MfgPackagingProfile::where('business_id', $business_id)->findOrFail($id);
            $profile->delete();
            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function getMaterialRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $this->authorizePackaging($business_id, true);

        $row_index = $request->input('row_index', 0);
        $variation_id = $request->input('variation_id');
        $material = null;

        if (! empty($variation_id)) {
            $variation = Variation::with('product')->find($variation_id);
            if (! empty($variation)) {
                $material = (object) [
                    'variation_id' => $variation->id,
                    'full_name' => $variation->full_name,
                ];
            }
        }

        return view('manufacturing::packaging_profile.material_row')
            ->with(compact('row_index', 'material'))
            ->render();
    }

    protected function syncMaterials(MfgPackagingProfile $profile, array $materials)
    {
        foreach ($materials as $material) {
            if (empty($material['variation_id'])) {
                continue;
            }

            MfgPackagingMaterial::create([
                'packaging_profile_id' => $profile->id,
                'variation_id' => $material['variation_id'],
                'quantity_per_container' => $material['quantity_per_container'] ?? null,
                'quantity_per_carton' => $material['quantity_per_carton'] ?? null,
                'material_role' => $material['material_role'] ?? null,
                'sub_unit_id' => $material['sub_unit_id'] ?? null,
            ]);
        }
    }
}