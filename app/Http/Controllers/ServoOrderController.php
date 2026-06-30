<?php

namespace App\Http\Controllers;

use App\ServoOrderLog;
use App\Utils\ContactUtil;
use Yajra\DataTables\Facades\DataTables;

class ServoOrderController extends Controller
{
    protected $contactUtil;

    public function __construct(ContactUtil $contactUtil)
    {
        $this->contactUtil = $contactUtil;
    }

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
                    'servo_order_logs.contact_id',
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
                    return $this->contactNameLink($row->customer_name, $row->contact_id);
                })
                ->editColumn('client_name', function ($row) {
                    return $this->contactNameLink($row->client_name, $row->contact_id);
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
                ->rawColumns(['status', 'local_order', 'action', 'customer_name', 'client_name'])
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

    public function clientDetails($contact_id)
    {
        if (! auth()->user()->can('sell.view') &&
            ! auth()->user()->can('direct_sell.view') &&
            ! auth()->user()->can('view_own_sell_only') &&
            ! auth()->user()->can('view_commission_agent_sell')) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('customer.view') &&
            ! auth()->user()->can('customer.view_own') &&
            ! auth()->user()->can('supplier.view') &&
            ! auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = $this->contactUtil->getContactInfo($business_id, $contact_id);

        if (empty($contact)) {
            abort(404);
        }

        return view('servo_orders.client_details_modal', compact('contact'));
    }

    private function contactNameLink(?string $name, ?int $contact_id): string
    {
        if (empty($name)) {
            return '-';
        }

        if (empty($contact_id)) {
            return e($name);
        }

        return '<a href="#" class="btn-modal" data-href="'.action([self::class, 'clientDetails'], [$contact_id]).'" data-container=".view_modal">'.e($name).'</a>';
    }
}
