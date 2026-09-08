<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\User;
use App\Support\TrafficAnalytics;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request)
    {
        $range = (int) $request->query('range', 7);
        if (! in_array($range, [7, 30, 90], true)) {
            $range = 7;
        }

        $since = now()->subDays($range);
        $prevSince = now()->subDays($range * 2);

        $views = PageView::where('created_at', '>=', $since)->count();
        $prevViews = PageView::whereBetween('created_at', [$prevSince, $since])->count();

        $uniqueVisitors = TrafficAnalytics::uniqueVisitors($since);
        $prevUniqueVisitors = TrafficAnalytics::uniqueVisitors($prevSince, $since);

        $liveVisitors = TrafficAnalytics::uniqueVisitors(now()->subMinutes(5));
        $adminsOnline = User::where('last_active_at', '>=', now()->subMinutes(5))->count();

        $topPages = TrafficAnalytics::topPages($since, 10);
        $topReferrers = TrafficAnalytics::topReferrers($since, 10);

        $sessionInsights = TrafficAnalytics::sessionInsights($since);
        $newVsReturning = TrafficAnalytics::newVsReturning($since);

        return view('admin.analytics', [
            'range' => $range,
            'views' => $views,
            'viewsDelta' => TrafficAnalytics::percentChange($views, $prevViews),
            'uniqueVisitors' => $uniqueVisitors,
            'uniqueVisitorsDelta' => TrafficAnalytics::percentChange($uniqueVisitors, $prevUniqueVisitors),
            'liveVisitors' => $liveVisitors,
            'adminsOnline' => $adminsOnline,
            'devices' => TrafficAnalytics::deviceBreakdown($since),
            'os' => TrafficAnalytics::osBreakdown($since),
            'browsers' => TrafficAnalytics::browserBreakdown($since),
            'languages' => TrafficAnalytics::languageBreakdown($since),
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
            'chart' => TrafficAnalytics::trendChart($range),
            'sessionInsights' => $sessionInsights,
            'newVsReturning' => $newVsReturning,
            'frequency' => TrafficAnalytics::frequencyBreakdown($since),
            'peakHours' => TrafficAnalytics::peakHours($since),
            'cohorts' => TrafficAnalytics::weeklyCohortRetention(8),
            'growth' => $this->growthComparisons(),
            'recentActivity' => $this->recentActivityItems(),
        ]);
    }

    /**
     * Polled by the "Luồng hoạt động gần đây" widget on the page — no
     * WebSocket infra here, so it just re-fetches this JSON every 20s.
     */
    public function recentActivity()
    {
        return response()->json(['items' => $this->recentActivityItems()]);
    }

    private function recentActivityItems(): array
    {
        return PageView::where('created_at', '>=', now()->subMinutes(30))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['path', 'referrer_host', 'device_type', 'created_at'])
            ->map(fn (PageView $p) => [
                'path' => $p->path,
                'referrer' => $p->referrer_host,
                'device' => $p->device_type,
                'time' => $p->created_at->diffForHumans(),
            ])->all();
    }

    /**
     * Real MoM/QoQ/YoY comparisons — current calendar period vs the same
     * length period immediately before it (not "vs same period last year"
     * calendar-aligned, since that needs >1 year of data this site won't
     * have yet; this still answers "are we growing period over period").
     */
    private function growthComparisons(): array
    {
        $now = now();

        $thisMonth = TrafficAnalytics::periodViews($now->copy()->startOfMonth(), $now->copy()->addSecond());
        $lastMonth = TrafficAnalytics::periodViews($now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->startOfMonth());

        $thisQuarter = TrafficAnalytics::periodViews($now->copy()->startOfQuarter(), $now->copy()->addSecond());
        $lastQuarter = TrafficAnalytics::periodViews($now->copy()->subQuarterNoOverflow()->startOfQuarter(), $now->copy()->startOfQuarter());

        $thisYear = TrafficAnalytics::periodViews($now->copy()->startOfYear(), $now->copy()->addSecond());
        $lastYear = TrafficAnalytics::periodViews($now->copy()->subYearNoOverflow()->startOfYear(), $now->copy()->startOfYear());

        return [
            'month' => ['current' => $thisMonth, 'previous' => $lastMonth, 'delta' => TrafficAnalytics::percentChange($thisMonth, $lastMonth)],
            'quarter' => ['current' => $thisQuarter, 'previous' => $lastQuarter, 'delta' => TrafficAnalytics::percentChange($thisQuarter, $lastQuarter)],
            'year' => ['current' => $thisYear, 'previous' => $lastYear, 'delta' => TrafficAnalytics::percentChange($thisYear, $lastYear)],
        ];
    }
}
