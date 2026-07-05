<?php

namespace App\Http\Controllers\LocationsFees;

use App\Http\Controllers\Controller;
use App\LocationsFees\City;
use App\Utils\LocationsFeesUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CityController extends Controller
{
    public function __construct(private LocationsFeesUtil $locationsFeesUtil) {}

    private function authorizeAccess(): void
    {
        if (! (auth()->user()->can('superadmin') || auth()->user()->can('locations_fees.access'))) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $rows = City::forBusiness($business_id)
                ->join('lf_governorates', 'lf_governorates.id', '=', 'lf_cities.governorate_id')
                ->select([
                    'lf_cities.id',
                    'lf_governorates.name as governorate_name',
                    'lf_cities.name',
                    'lf_cities.delivery_cost',
                    'lf_cities.is_active',
                ]);

            return DataTables::of($rows)
                ->editColumn('delivery_cost', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$row->delivery_cost.'</span>';
                })
                ->editColumn('is_active', function ($row) {
                    return (int) $row->is_active === 1
                        ? '<span class="label bg-green">'.__('business.is_active').'</span>'
                        : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([self::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                    <button data-href="'.action([self::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_city_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns([2, 3, 4])
                ->make(false);
        }

        return view('locations_fees.cities.index');
    }

    public function create()
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');
        $governorates = $this->locationsFeesUtil->governoratesForDropdown($business_id);

        return view('locations_fees.cities.create')->with(compact('governorates'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            City::create([
                'business_id' => $business_id,
                'governorate_id' => $request->input('governorate_id'),
                'name' => $request->input('name'),
                'delivery_cost' => $this->num_uf($request->input('delivery_cost')),
                'is_active' => ! empty($request->input('is_active')) ? 1 : 0,
            ]);

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');
        $city = City::forBusiness($business_id)->findOrFail($id);
        $governorates = $this->locationsFeesUtil->governoratesForDropdown($business_id);

        return view('locations_fees.cities.edit')->with(compact('city', 'governorates'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            $city = City::forBusiness($business_id)->findOrFail($id);
            $city->update([
                'governorate_id' => $request->input('governorate_id'),
                'name' => $request->input('name'),
                'delivery_cost' => $this->num_uf($request->input('delivery_cost')),
                'is_active' => ! empty($request->input('is_active')) ? 1 : 0,
            ]);

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            City::forBusiness($business_id)->where('id', $id)->delete();
            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    private function num_uf($input)
    {
        return is_string($input) ? str_replace(',', '', $input) : $input;
    }
}
