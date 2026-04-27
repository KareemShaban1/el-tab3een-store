<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use App\Transaction;
use App\BusinessLocation;
use Carbon\Carbon;
use DB;
use App\TransactionPayment;
use App\Events\TransactionPaymentAdded;
use App\Utils\Util;
use App\Utils\ModuleUtil;

class EcommerceSellController extends Controller
{
	protected $transactionUtil;
	protected $commonUtil;
	protected $moduleUtil;

	public function __construct(TransactionUtil $transactionUtil, Util $commonUtil, ModuleUtil $moduleUtil)
	{
		$this->transactionUtil = $transactionUtil;
		$this->commonUtil = $commonUtil;
		$this->moduleUtil = $moduleUtil;
	}


      /**
     * Update ecommerce order status from backoffice.
     */
    public function updateEcommerceStatus(Request $request, $id)
    {
        if (! auth()->user()->can('sell.update')) {
            return $this->respondUnauthorized();
        }

        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('source', 'ecommerce')
            ->findOrFail($id);

        $validated = $request->validate([
            'ecommerce_order_status' => 'nullable|string|max:60',
            'shipping_status' => 'nullable|string|max:60',
            'payment_status' => 'nullable|string|max:60',
            'delivered_to' => 'nullable|string|max:255',
            'delivery_person' => 'nullable|integer',
            'shipping_details' => 'nullable|string',
        ]);

        if ($request->filled('ecommerce_order_status')) {
            $transaction->ecommerce_order_status = $validated['ecommerce_order_status'];
            // Backward compatibility for existing flows still using sub_status.
            $transaction->sub_status = 'ecommerce_' . $validated['ecommerce_order_status'];
        }
        if ($request->filled('shipping_status')) {
            $transaction->shipping_status = $validated['shipping_status'];
        }
        if ($request->filled('payment_status')) {
            $transaction->payment_status = $validated['payment_status'];


 	$salePaymentData = [
            'transaction_id' => $transaction->id,
            'business_id' => $transaction->business_id,
            'amount' => $transaction->final_total,
            'business_location_id' => $transaction->location_id,
            'method' => 'cash',
            'note' => ''
        ];
        switch ($request->payment_status) {
            case 'due':
          //       $this->moduleUtil->activityLog($transaction, 'change_payment_status', null, ['order_number' => $transaction->number, 'status' => 'pending']);
                break;
            case 'paid':
                $this->moduleUtil->activityLog($transaction, 'change_payment_status', null, ['order_number' => $transaction->number, 'status' => 'paid']);
                $this->makeSalePayment($salePaymentData);
                $this->moduleUtil->activityLog(
                    $transaction,
                    'change_payment_status',
                    null,
                    ['order_invoice_no' => $transaction->invoice_no, 'status' => 'paid']
                );
                break;
            case 'not_paid':
          //       $this->moduleUtil->activityLog($transaction, 'change_payment_status', null, ['order_invoice_no' => $transaction->invoice_no, 'status' => 'failed']);
             
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $request->payment_status");
        }
        }
        if ($request->filled('delivered_to')) {
            $transaction->delivered_to = $validated['delivered_to'];
        }
        if ($request->filled('delivery_person')) {
            $transaction->delivery_person = $validated['delivery_person'];
        }
        if ($request->filled('shipping_details')) {
            $transaction->shipping_details = $validated['shipping_details'];
        }
        $transaction->save();



        $this->transactionUtil->activityLog($transaction, 'edited');

	if ($request->ajax()) {
	return $this->respond([
		'success' => true,
		'msg' => 'E-commerce order status updated successfully.',
		]);
	}

		return redirect()->back()->with('status', [
		'success' => true,
		'msg' => 'E-commerce order status updated successfully.',
		]);

    }

 protected function makeSalePayment($salePaymentData)
    {
        \Log::info('Ecommerce payment flow started', [
            'transaction_id' => $salePaymentData['transaction_id'] ?? null,
            'business_id' => $salePaymentData['business_id'] ?? null,
            'amount' => $salePaymentData['amount'] ?? null,
            'method' => $salePaymentData['method'] ?? null,
        ]);

        try {
            $business_id = $salePaymentData['business_id'];
            $transaction_id = $salePaymentData['transaction_id'];
            $transaction = Transaction::where('business_id', $business_id)->with(['contact'])->findOrFail($transaction_id);

            $location = BusinessLocation::find($salePaymentData['business_location_id']);
            $transaction_before = $transaction->replicate();

            \Log::info('Ecommerce payment transaction loaded', [
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'current_payment_status' => $transaction->payment_status,
                'final_total' => $transaction->final_total,
                'location_id' => $salePaymentData['business_location_id'] ?? null,
                'location_found' => ! empty($location),
            ]);

            if ($transaction->payment_status != 'paid') {
                // $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                // 'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                // 'cheque_number', 'bank_account_number']);
                $salePaymentData['paid_on'] = Carbon::now();
                $salePaymentData['transaction_id'] = $transaction->id;
                $salePaymentData['amount'] = $this->transactionUtil->num_uf($salePaymentData['amount']);
                // $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $salePaymentData['created_by'] = 1;
                $salePaymentData['payment_for'] = $transaction->contact_id;

                // $salePaymentData['account_id'] =2;
                if (!empty($location->default_payment_accounts)) {
                    $default_payment_accounts = json_decode(
                        $location->default_payment_accounts,
                        true
                    );
                    // Check for cash account and set account_id
                    if (!empty($default_payment_accounts['cash']['is_enabled']) && !empty($default_payment_accounts['cash']['account'])) {
                        $salePaymentData['account_id'] = $default_payment_accounts['cash']['account'] ?? 1;
                    }
                }

                \Log::info('Ecommerce payment account resolution', [
                    'transaction_id' => $transaction_id,
                    'has_default_accounts' => ! empty($location?->default_payment_accounts),
                    'account_id' => $salePaymentData['account_id'] ?? null,
                ]);


                $prefix_type = 'purchase_payment';
                if (in_array($transaction->type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                } elseif (in_array($transaction->type, ['expense', 'expense_refund'])) {
                    $prefix_type = 'expense_payment';
                }

                DB::beginTransaction();

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $salePaymentData['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                //Pay from advance balance
                $payment_amount = $salePaymentData['amount'];
                
                \Log::info('salePaymentData', [$salePaymentData]);

                if (!empty($salePaymentData['amount'])) {
                    $tp = TransactionPayment::create($salePaymentData);
                    $salePaymentData['transaction_type'] = $transaction->type;
                    event(new TransactionPaymentAdded($tp, $salePaymentData));
                    \Log::info('Ecommerce payment transaction created', [
                        'transaction_id' => $transaction_id,
                        'transaction_payment_id' => $tp->id ?? null,
                        'payment_ref_no' => $salePaymentData['payment_ref_no'] ?? null,
                        'amount' => $salePaymentData['amount'] ?? null,
                    ]);
                } else {
                    \Log::warning('Ecommerce payment skipped: empty amount', [
                        'transaction_id' => $transaction_id,
                        'raw_amount' => $salePaymentData['amount'] ?? null,
                    ]);
                }

                //update payment status
                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);
                $transaction->payment_status = $payment_status;

                $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);

                DB::commit();
            } else {
                \Log::warning('Ecommerce payment skipped: transaction already paid', [
                    'transaction_id' => $transaction_id,
                    'invoice_no' => $transaction->invoice_no,
                    'payment_status' => $transaction->payment_status,
                ]);
            }

            $output = [
                'success' => true,
                'msg' => __('purchase.payment_added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = __('messages.something_went_wrong');
            \Log::error('Ecommerce payment creation failed', [
                'transaction_id' => $salePaymentData['transaction_id'] ?? null,
                'business_id' => $salePaymentData['business_id'] ?? null,
                'payload' => $salePaymentData,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);


            $output = [
                'success' => false,
                'msg' => $msg
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}