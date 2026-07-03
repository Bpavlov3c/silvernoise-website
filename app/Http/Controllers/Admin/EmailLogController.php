<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = EmailLog::with('user:id,name,surname,email')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('to_email', 'ilike', "%{$request->search}%"))
            ->latest('sent_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
