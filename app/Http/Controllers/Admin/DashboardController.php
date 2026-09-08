<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Support\TrafficAnalytics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $postTypes = get_post_types();

        $counts = collect($postTypes)->mapWithKeys(fn (array $type, string $slug) => [
            $slug => [
                'label' => $type['label'],
                'total' => Post::ofType($slug)->count(),
                'published' => Post::ofType($slug)->published()->count(),
            ],
        ]);

        $marketing = new MarketingController;

        return view('admin.dashboard', [
            'user' => $request->user(),
            'counts' => $counts,
            'mediaCount' => Media::count(),
            'userCount' => User::count(),
            'recentPosts' => Post::with('author')->latest()->limit(5)->get(),
            'recentContacts' => ContactMessage::latest()->limit(5)->get(),
            'contentPerformance' => $marketing->contentPerformance(),
            'leadSources' => $marketing->leadSources()->take(5),
            'crmStatusCounts' => $this->crmStatusCounts(),
            'healthSummary' => (new SiteHealthController)->summary(),
            ...$this->quickKpis(),
        ]);
    }

    /**
     * Just enough traffic signal for a 5-second glance — the actual
     * breakdowns (trend chart, top pages/referrers, devices) live on
     * /admin/analytics now so this page stays a quick overview, not a
     * second copy of Analytics.
     */
    private function quickKpis(): array
    {
        $currentStart = now()->subDays(7);
        $previousStart = now()->subDays(14);

        $weekViews = PageView::where('created_at', '>=', $currentStart)->count();
        $prevWeekViews = PageView::whereBetween('created_at', [$previousStart, $currentStart])->count();

        $weekUniqueVisitors = TrafficAnalytics::uniqueVisitors($currentStart);
        $prevWeekUniqueVisitors = TrafficAnalytics::uniqueVisitors($previousStart, $currentStart);

        $liveVisitors = TrafficAnalytics::uniqueVisitors(now()->subMinutes(5));

        $weekLeads = ContactMessage::where('created_at', '>=', $currentStart)->count();
        $prevWeekLeads = ContactMessage::whereBetween('created_at', [$previousStart, $currentStart])->count();

        $conversionRate = $weekViews > 0 ? round($weekLeads / $weekViews * 100, 1) : null;

        $topPages = TrafficAnalytics::topPages($currentStart, 5);
        $topReferrers = TrafficAnalytics::topReferrers($currentStart, 5);

        return [
            'weekViews' => $weekViews,
            'weekViewsDelta' => TrafficAnalytics::percentChange($weekViews, $prevWeekViews),
            'weekUniqueVisitors' => $weekUniqueVisitors,
            'weekUniqueVisitorsDelta' => TrafficAnalytics::percentChange($weekUniqueVisitors, $prevWeekUniqueVisitors),
            'liveVisitors' => $liveVisitors,
            'weekLeads' => $weekLeads,
            'weekLeadsDelta' => TrafficAnalytics::percentChange($weekLeads, $prevWeekLeads),
            'conversionRate' => $conversionRate,
            'chart' => TrafficAnalytics::trendChart(14),
            'devices' => TrafficAnalytics::deviceBreakdown($currentStart),
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
        ];
    }

    private function crmStatusCounts(): array
    {
        return [
            'all' => ContactMessage::count(),
            ...ContactMessage::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
        ];
    }
}
