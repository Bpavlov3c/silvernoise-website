<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncKvzReleases;
use App\Models\ApiLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ApiLog::with('triggeredBy:id,name,surname')
            ->when($request->source, fn($q) => $q->where('source', $request->source))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }

    public function triggerKvzSync(Request $request): JsonResponse
    {
        SyncKvzReleases::dispatch($request->user()->id);

        return response()->json(['message' => 'KVZ sync job queued.']);
    }
}
