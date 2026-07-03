<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = PaymentRequest::with(['customer', 'report.label'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('requested_at')
            ->paginate(25);

        return response()->json(PaymentRequestResource::collection($payments));
    }

    public function show(int $id): JsonResponse
    {
        $payment = PaymentRequest::with(['customer', 'report.label'])->findOrFail($id);

        return response()->json(new PaymentRequestResource($payment));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'      => 'required|in:pending,processing,sent,completed,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $payment = PaymentRequest::findOrFail($id);
        $payment->update([
            'status'       => $request->status,
            'admin_notes'  => $request->admin_notes,
            'processed_at' => in_array($request->status, ['sent', 'completed']) ? now() : null,
        ]);

        // If completed, mark the associated report as paid
        if ($request->status === 'completed') {
            $payment->report->update(['status' => 'paid', 'paid_at' => now()]);

            // TODO: Send payment notification email
            // SendPaymentNotification::dispatch($payment);
        }

        return response()->json(new PaymentRequestResource($payment->fresh('customer', 'report')));
    }
}
