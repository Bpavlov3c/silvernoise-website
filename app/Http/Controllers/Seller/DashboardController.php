<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user      = $request->user();
        $labelIds  = $user->labels()->pluck('labels.id');

        // Total and current-period earnings
        $totalEarnings   = Report::where('customer_id', $user->id)->sum('total_earnings');
        $currentYear     = now()->year;
        $currentEarnings = Report::where('customer_id', $user->id)
            ->whereYear('period_start', $currentYear)
            ->sum('total_earnings');

        // Release counts by status
        $releaseCounts = Release::where('customer_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Latest release
        $latestRelease = Release::where('customer_id', $user->id)
            ->with('label')
            ->latest()
            ->first(['id', 'title', 'status', 'label_id', 'cover_art_url', 'created_at']);

        // Unpaid reports count (alert)
        $unpaidCount = Report::where('customer_id', $user->id)
            ->where('status', 'unpaid')
            ->count();

        // Earnings trend (last 12 months)
        $trend = Report::where('customer_id', $user->id)
            ->selectRaw("TO_CHAR(period_start, 'Mon YYYY') as month, SUM(total_earnings) as earnings")
            ->where('period_start', '>=', now()->subMonths(12)->startOfMonth())
            ->groupByRaw("TO_CHAR(period_start, 'Mon YYYY'), EXTRACT(YEAR FROM period_start), EXTRACT(MONTH FROM period_start)")
            ->orderByRaw("EXTRACT(YEAR FROM MIN(period_start)), EXTRACT(MONTH FROM MIN(period_start))")
            ->get();

        return response()->json([
            'total_earnings'   => $totalEarnings,
            'current_earnings' => $currentEarnings,
            'release_counts'   => $releaseCounts,
            'latest_release'   => $latestRelease,
            'unpaid_reports'   => $unpaidCount,
            'earnings_trend'   => $trend,
        ]);
    }
}
