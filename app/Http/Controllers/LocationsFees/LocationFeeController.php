<?php

namespace App\Http\Controllers\LocationsFees;

use App\Http\Controllers\Controller;
use App\Utils\LocationsFeesUtil;

class LocationFeeController extends Controller
{
    public function __construct(private LocationsFeesUtil $locationsFeesUtil) {}

    public function index()
    {
        if (! (auth()->user()->can('superadmin') || auth()->user()->can('locations_fees.access'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) request()->session()->get('user.business_id');
        $governorates = $this->locationsFeesUtil->governoratesForDropdown($business_id);

        return view('locations_fees.index', compact('governorates'));
    }
}
