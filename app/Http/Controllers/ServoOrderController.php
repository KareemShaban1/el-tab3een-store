<?php

namespace App\Http\Controllers;

use App\ServoOrderLog;
use Yajra\DataTables\Facades\DataTables;

class ServoOrderController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('sell.view') &&
            ! auth()->user()->can('direct_sell.view') &&
            ! auth()->user()->can('view_own_sell_only') &&
            ! auth()->user()->can('view_commission_agent_sell')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $logs = ServoOrderLog::query()
                ->where('servo_order_logs.business_id', $business_id)
                ->leftJoin('contacts', 'contacts.id', '=', 'servo_order_logs.contact_id')
                ->leftJoin('transactions', 'transactions.id', '=', 'servo_order_logs.transaction_id')
                ->select([
                    'servo_order_logs.id',
                    'servo_order_logs.created_at',
                    'servo_order_logs.client_name',
                    'servo_order_logs.status',
                    'servo_order_logs.items',
                    'servo_order_logs.transaction_id',
                    'servo_order_logs.servo_reference',
                    'servo_order_logs.http_status',
                    'servo_order_logs.error_message',
                    'contacts.name as customer_name',
                    'transactions.invoice_no',
                ]);

            if (request()->filled('status')) {
                $logs->where('servo_order_logs.status', request()->input('status'));
            }

            return DataTables::of($logs)
                ->editColumn('created_at', function ($row) {
                    return optional($row->created_at)->format('Y-m-d H:i:s');
                })
                ->editColumn('customer_name', function ($row) {
                    return $row->customer_name ?: '-';
                })
                ->editColumn('status', function ($row) {
                    $labels = [
                        'pending' => 'label-warning',
                        'success' => 'label-success',
                        'failed' => 'label-danger',
                    ];
                    $class = $labels[$row->status] ?? 'label-default';

                    return '<span class="label '.$class.'">'.e(ucfirst($row->status)).'</span>';
                })
                ->addColumn('items_count', function ($row) {
                    $items = is_array($row->items) ? $row->items : json_decode($row->items ?? '[]', true);

                    return is_array($items) ? count($items) : 0;
                })
                ->addColumn('local_order', function ($row) {
                    if (empty($row->transaction_id)) {
                        return '-';
                    }

                    $label = $row->invoice_no ?: '#'.$row->transaction_id;

                    return '<a href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]).'">'.e($label).'</a>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([self::class, 'show'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary">'.__('messages.view').'</a>';
                })
                ->rawColumns(['status', 'local_order', 'action'])
                ->make(true);
        }

        return view('servo_orders.index');
    }

    public function show($id)
    {
        if (! auth()->user()->can('sell.view') &&
            ! auth()->user()->can('direct_sell.view') &&
            ! auth()->user()->can('view_own_sell_only') &&
            ! auth()->user()->can('view_commission_agent_sell')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $log = ServoOrderLog::with(['contact', 'transaction'])
            ->where('business_id', $business_id)
            ->findOrFail($id);

        return view('servo_orders.show', compact('log'));
    }
}
