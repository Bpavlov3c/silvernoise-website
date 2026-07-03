<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Models\Release;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'total_customers'       => User::where('role', 'seller')->count(),
                'active_customers'      => User::where('role', 'seller')->where('is_active', true)->count(),
                'total_releases'        => Release::count(),
                'live_releases'         => Release::where('status', 'live')->count(),
                'pending_releases'      => Release::where('status', 'pending')->count(),
                'unpaid_reports'        => Report::where('status', 'unpaid')->count(),
                'pending_payments'      => PaymentRequest::where('status', 'pending')->count(),
                'total_earnings_eur'    => Report::where('status', 'paid')->sum('total_earnings'),
                'pending_earnings_eur'  => Report::where('status', '!=', 'paid')->sum('total_earnings'),
            ],
            'recent_releases'   => Release::with(['label', 'customer'])
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'status', 'label_id', 'customer_id', 'created_at']),
            'payment_queue'     => PaymentRequest::with(['customer', 'report'])
                ->where('status', 'pending')
                ->latest('requested_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
