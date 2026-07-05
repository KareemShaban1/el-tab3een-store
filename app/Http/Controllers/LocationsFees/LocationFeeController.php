<?php

namespace App\Http\Controllers\LocationsFees;

use App\Http\Controllers\Controller;

class LocationFeeController extends Controller
{
    public function index()
    {
        if (! (auth()->user()->can('superadmin') || auth()->user()->can('locations_fees.access'))) {
            abort(403, 'Unauthorized action.');
        }

        return view('locations_fees.index');
    }
}
