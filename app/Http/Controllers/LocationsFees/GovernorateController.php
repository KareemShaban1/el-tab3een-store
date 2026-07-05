<?php

namespace App\Http\Controllers\LocationsFees;

use App\Http\Controllers\Controller;
use App\LocationsFees\Governorate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GovernorateController extends Controller
{
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
            $rows = Governorate::forBusiness($business_id)->select(['id', 'name', 'is_active']);

            return DataTables::of($rows)
                ->editColumn('is_active', function ($row) {
                    return (int) $row->is_active === 1
                        ? '<span class="label bg-green">'.__('business.is_active').'</span>'
                        : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([self::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                    <button data-href="'.action([self::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_governorate_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns([1, 2])
                ->make(false);
        }

        return view('locations_fees.governorates.index');
    }

    public function create()
    {
        $this->authorizeAccess();

        return view('locations_fees.governorates.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            Governorate::create([
                'business_id' => $business_id,
                'name' => $request->input('name'),
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
        $governorate = Governorate::forBusiness($business_id)->findOrFail($id);

        return view('locations_fees.governorates.edit')->with(compact('governorate'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            $governorate = Governorate::forBusiness($business_id)->findOrFail($id);
            $governorate->update([
                'name' => $request->input('name'),
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
            Governorate::forBusiness($business_id)->where('id', $id)->delete();
            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
