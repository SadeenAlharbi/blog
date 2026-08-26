<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function index(Request $request)
    {
        $period = $this->analytics->normalizePeriod($request->query('period'));
        $days = $this->analytics->days($period);

        return view('admin.analytics.index', [
            'period' => $period,
            'periods' => AnalyticsService::PERIODS,
            'days' => $days,
            'overview' => $this->analytics->overview(),
            'views' => $this->analytics->viewsOverTime($days),
            'posts' => $this->analytics->postsOverTime($days),
            'comments' => $this->analytics->commentsOverTime($days),
            'topPosts' => $this->analytics->topPosts(10),
            'topCategories' => $this->analytics->topCategories(8),
            'topCategoriesByViews' => $this->analytics->topCategoriesByViews(8),
            'topAuthors' => $this->analytics->topAuthors(8),
        ]);
    }
}
