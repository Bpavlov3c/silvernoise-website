<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = Report::where('customer_id', $request->user()->id)
            ->with('label')
            ->when($request->year, fn($q) => $q->whereYear('period_start', $request->year))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('report_date')
            ->paginate(20);

        return response()->json(ReportResource::collection($reports));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $report = Report::where('customer_id', $request->user()->id)
            ->with(['label', 'earnings', 'paymentRequest'])
            ->findOrFail($id);

        return response()->json(new ReportResource($report));
    }

    public function download(Request $request, int $id): JsonResponse
    {
        $report = Report::where('customer_id', $request->user()->id)->findOrFail($id);

        // Generate a fresh signed URL valid for 15 minutes
        $url = Storage::disk('r2')->temporaryUrl($report->file_path, now()->addMinutes(15));

        return response()->json(['download_url' => $url]);
    }
}
