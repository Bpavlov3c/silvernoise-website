<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = Report::with(['label', 'customer'])
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->label_id,    fn($q) => $q->where('label_id', $request->label_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->when($request->year, fn($q) =>
                $q->whereYear('period_start', $request->year)
            )
            ->latest('report_date')
            ->paginate(25);

        return response()->json(ReportResource::collection($reports));
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = "reports/{$request->customer_id}/{$request->period_label}_{$file->getClientOriginalName()}";
        Storage::disk('r2')->put($path, $file->getContent());

        $report = Report::create([
            ...$request->validated(),
            'file_path' => $path,
            'file_url'  => Storage::disk('r2')->temporaryUrl($path, now()->addDays(7)),
        ]);

        return response()->json(new ReportResource($report->load('label', 'customer')), 201);
    }

    public function show(int $id): JsonResponse
    {
        $report = Report::with(['label', 'customer', 'earnings', 'paymentRequest'])
            ->findOrFail($id);

        return response()->json(new ReportResource($report));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        $data = $request->validate([
            'name'           => 'sometimes|string',
            'total_earnings' => 'sometimes|numeric|min:0',
            'status'         => 'sometimes|in:unpaid,payment_requested,paid',
            'paid_at'        => 'nullable|date',
        ]);

        $report->update($data);

        return response()->json(new ReportResource($report->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        Storage::disk('r2')->delete($report->file_path);
        $report->delete();

        return response()->json(['message' => 'Report deleted.']);
    }
}
