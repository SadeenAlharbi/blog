<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboard,
        private readonly AnalyticsService $analytics,
    ) {
    }

    public function index(Request $request)
    {
        $period = $this->analytics->normalizePeriod($request->query('period'));
        $days = $this->analytics->days($period);

        $stats = $this->dashboard->getOverviewStats($days);

        return view('admin.dashboard', [
            'period' => $period,
            'periods' => AnalyticsService::PERIODS,
            'stats' => $stats,
            'growth' => $this->dashboard->getContentGrowth($days),
            'topCategories' => $this->dashboard->getTopCategories(),
            'mostViewed' => $this->dashboard->getMostViewedPosts(),
            'latestPosts' => $this->dashboard->getLatestPosts(),
            'latestComments' => $this->dashboard->getLatestComments(),
            // Reuses the counts already in $stats — see getNeedsAttention().
            'needsAttention' => $this->dashboard->getNeedsAttention($stats),
            'topAuthors' => $this->dashboard->getTopAuthors(),
            'activity' => $this->dashboard->getRecentActivity(),
        ]);
    }
}
