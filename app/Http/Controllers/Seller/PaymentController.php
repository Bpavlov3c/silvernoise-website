<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StorePaymentRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = PaymentRequest::where('customer_id', $request->user()->id)
            ->with('report.label')
            ->latest('requested_at')
            ->paginate(20);

        return response()->json(PaymentRequestResource::collection($payments));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $report = Report::where('customer_id', $request->user()->id)
            ->where('status', 'unpaid')
            ->findOrFail($request->report_id);

        // Upload invoice if provided
        $invoicePath = null;
        $invoiceUrl  = null;
        if ($request->hasFile('invoice')) {
            $file        = $request->file('invoice');
            $invoicePath = "invoices/{$request->user()->id}/{$report->id}_{$file->getClientOriginalName()}";
            Storage::disk('r2')->put($invoicePath, $file->getContent());
            $invoiceUrl = Storage::disk('r2')->temporaryUrl($invoicePath, now()->addDays(30));
        }

        $payment = PaymentRequest::create([
            'report_id'      => $report->id,
            'customer_id'    => $request->user()->id,
            'amount'         => $report->total_earnings,
            'currency'       => $report->currency,
            'iban'           => $request->iban,
            'bank_name'      => $request->bank_name,
            'account_holder' => $request->account_holder,
            'invoice_path'   => $invoicePath,
            'invoice_url'    => $invoiceUrl,
        ]);

        // Mark report as payment_requested
        $report->update(['status' => 'payment_requested']);

        // TODO: Notify admin via email
        // NotifyAdminNewPaymentRequest::dispatch($payment);

        return response()->json(new PaymentRequestResource($payment), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $payment = PaymentRequest::where('customer_id', $request->user()->id)
            ->with('report.label')
            ->findOrFail($id);

        return response()->json(new PaymentRequestResource($payment));
    }
}
