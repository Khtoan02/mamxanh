<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PageView;
use Illuminate\Support\Facades\DB;

/**
 * Shared query helpers for the self-hosted `page_views` traffic data —
 * used by both the Dashboard overview and the deeper /admin/analytics page
 * so the two never drift into slightly-different numbers for the same
 * underlying data.
 */
class TrafficAnalytics
{
    private const DEVICE_LABELS = [
        'desktop' => 'Máy tính',
        'mobile' => 'Điện thoại',
        'tablet' => 'Máy tính bảng',
    ];

    private const SEARCH_ENGINE_HOSTS = ['google', 'bing', 'yahoo', 'duckduckgo', 'coccoc', 'cốc cốc', 'yandex', 'baidu'];

    private const SOCIAL_HOSTS = ['facebook', 'fb.com', 'instagram', 'tiktok', 'zalo', 'youtube', 'twitter', 'x.com', 'linkedin', 'threads', 'messenger'];

    private const CHANNEL_ORDER = ['Trực tiếp', 'Tìm kiếm tự nhiên', 'Mạng xã hội', 'Giới thiệu'];


    public static function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * SVG area-chart data for the last $days days, ending today. Coordinates
     * are pre-computed here (not in Blade) so the view just loops and renders.
     */
    public static function trendChart(int $days): array
    {
        $byDay = PageView::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, count(*) as views')
            ->groupBy('day')
            ->pluck('views', 'day');

        $series = collect(range($days - 1, 0))->map(fn (int $daysAgo) => [
            'date' => today()->subDays($daysAgo),
            'views' => (int) ($byDay[today()->subDays($daysAgo)->toDateString()] ?? 0),
        ]);

        $maxViews = max(1, $series->max('views'));
        $width = 600;
        $height = 120;
        $baseline = $height - 10;
        $lastIndex = $series->count() - 1;
        $step = $width / max(1, $lastIndex);
        // Skip every Nth label so a 90-day chart doesn't cram 90 date labels
        // together — aim for roughly 10 visible labels regardless of range.
        $labelEvery = max(1, (int) ceil($series->count() / 10));

        $points = $series->values()->map(function (array $day, int $i) use ($step, $maxViews, $baseline, $lastIndex, $labelEvery) {
            return [
                'x' => round($i * $step, 1),
                'y' => round($baseline - ($day['views'] / $maxViews * ($baseline - 10)), 1),
                'views' => $day['views'],
                'label' => $day['date']->format('d/m'),
                'showLabel' => $i % $labelEvery === 0 || $i === $lastIndex,
            ];
        });

        $polyline = $points->map(fn (array $p) => "{$p['x']},{$p['y']}")->implode(' ');
        $areaPath = 'M'.$points->first()['x'].','.$baseline.' L'.$points->map(fn (array $p) => "{$p['x']},{$p['y']}")->implode(' L').' L'.$points->last()['x'].','.$baseline.' Z';

        return [
            'width' => $width,
            'height' => $height,
            'baseline' => $baseline,
            'points' => $points,
            'polyline' => $polyline,
            'areaPath' => $areaPath,
            'maxViews' => $maxViews,
        ];
    }

    public static function topPages(\Illuminate\Support\Carbon $since, int $limit = 5)
    {
        return PageView::where('created_at', '>=', $since)
            ->select('path', DB::raw('count(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public static function topReferrers(\Illuminate\Support\Carbon $since, int $limit = 5)
    {
        return PageView::where('created_at', '>=', $since)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as views'))
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public static function deviceBreakdown(\Illuminate\Support\Carbon $since)
    {
        $counts = PageView::where('created_at', '>=', $since)
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('count(*) as views'))
            ->groupBy('device_type')
            ->pluck('views', 'device_type');

        $total = max(1, $counts->sum());

        return collect(self::DEVICE_LABELS)->map(fn (string $label, string $type) => [
            'type' => $type,
            'label' => $label,
            'views' => (int) ($counts[$type] ?? 0),
            'pct' => (int) round(($counts[$type] ?? 0) / $total * 100),
        ])->values();
    }

    public static function uniqueVisitors(\Illuminate\Support\Carbon $since, ?\Illuminate\Support\Carbon $until = null): int
    {
        $query = PageView::where('created_at', '>=', $since);

        if ($until) {
            $query->where('created_at', '<', $until);
        }

        return (int) $query->distinct('visitor_id')->count('visitor_id');
    }

    public static function periodViews(\Illuminate\Support\Carbon $since, \Illuminate\Support\Carbon $until): int
    {
        return (int) PageView::where('created_at', '>=', $since)->where('created_at', '<', $until)->count();
    }

    /**
     * Groups raw pageviews into sessions per visitor using a 30-minute
     * inactivity gap — the industry-standard heuristic (GA uses the same
     * default) since there is no client-side session-end signal here.
     */
    private static function buildSessions(\Illuminate\Support\Carbon $since, ?\Illuminate\Support\Carbon $until = null): \Illuminate\Support\Collection
    {
        $query = PageView::where('created_at', '>=', $since)->whereNotNull('visitor_id');

        if ($until) {
            $query->where('created_at', '<', $until);
        }

        $rows = $query->orderBy('visitor_id')->orderBy('created_at')->get(['visitor_id', 'created_at', 'path']);

        $sessions = collect();
        $current = null;

        foreach ($rows as $row) {
            $ts = \Illuminate\Support\Carbon::parse($row->created_at);

            if ($current && $current['visitor_id'] === $row->visitor_id && abs($current['last_ts']->diffInMinutes($ts)) <= 30) {
                $current['pages']++;
                $current['exit_path'] = $row->path;
                $current['last_ts'] = $ts;
            } else {
                if ($current) {
                    $sessions->push($current);
                }
                $current = [
                    'visitor_id' => $row->visitor_id,
                    'start' => $ts,
                    'last_ts' => $ts,
                    'entry_path' => $row->path,
                    'exit_path' => $row->path,
                    'pages' => 1,
                ];
            }
        }

        if ($current) {
            $sessions->push($current);
        }

        return $sessions->map(function (array $s) {
            $s['duration'] = abs($s['start']->diffInSeconds($s['last_ts']));

            return $s;
        });
    }

    /**
     * Session count, avg duration, avg pages/session, bounce rate, and
     * top entry/exit pages — all derived from one session pass so the
     * page only pays the session-building cost once.
     */
    public static function sessionInsights(\Illuminate\Support\Carbon $since, ?\Illuminate\Support\Carbon $until = null, int $topPagesLimit = 5): array
    {
        $sessions = self::buildSessions($since, $until);
        $count = $sessions->count();

        if ($count === 0) {
            return [
                'count' => 0,
                'avgDurationSeconds' => 0,
                'avgPagesPerSession' => 0.0,
                'bounceRatePct' => 0,
                'entryPages' => collect(),
                'exitPages' => collect(),
            ];
        }

        $bounced = $sessions->filter(fn (array $s) => $s['pages'] === 1)->count();

        return [
            'count' => $count,
            'avgDurationSeconds' => (int) round($sessions->avg('duration')),
            'avgPagesPerSession' => round($sessions->avg('pages'), 1),
            'bounceRatePct' => (int) round($bounced / $count * 100),
            'entryPages' => $sessions->countBy('entry_path')->sortDesc()->take($topPagesLimit)
                ->map(fn (int $c, string $p) => ['path' => $p, 'count' => $c])->values(),
            'exitPages' => $sessions->countBy('exit_path')->sortDesc()->take($topPagesLimit)
                ->map(fn (int $c, string $p) => ['path' => $p, 'count' => $c])->values(),
        ];
    }

    /**
     * "New" = a visitor's very first pageview (ever) falls inside the
     * period; "returning" = they had at least one pageview before it.
     */
    public static function newVsReturning(\Illuminate\Support\Carbon $since, ?\Illuminate\Support\Carbon $until = null): array
    {
        $visitorIds = PageView::where('created_at', '>=', $since)
            ->when($until, fn ($q) => $q->where('created_at', '<', $until))
            ->whereNotNull('visitor_id')
            ->distinct()
            ->pluck('visitor_id');

        $total = $visitorIds->count();

        if ($total === 0) {
            return ['new' => 0, 'returning' => 0, 'newPct' => 0, 'returningPct' => 0];
        }

        $returning = PageView::where('created_at', '<', $since)
            ->whereIn('visitor_id', $visitorIds)
            ->distinct()
            ->count('visitor_id');

        $new = $total - $returning;

        return [
            'new' => $new,
            'returning' => $returning,
            'newPct' => (int) round($new / $total * 100),
            'returningPct' => (int) round($returning / $total * 100),
        ];
    }

    /**
     * How many distinct calendar days each visitor showed up within the
     * period — a real "how often do people come back" signal without
     * needing per-visitor CRM profiles.
     */
    public static function frequencyBreakdown(\Illuminate\Support\Carbon $since): array
    {
        $daysPerVisitor = PageView::where('created_at', '>=', $since)
            ->whereNotNull('visitor_id')
            ->select('visitor_id', DB::raw('count(distinct date(created_at)) as days'))
            ->groupBy('visitor_id')
            ->pluck('days');

        $total = max(1, $daysPerVisitor->count());
        $buckets = ['1 lần' => 0, '2-3 lần' => 0, '4+ lần' => 0];

        foreach ($daysPerVisitor as $days) {
            if ($days <= 1) {
                $buckets['1 lần']++;
            } elseif ($days <= 3) {
                $buckets['2-3 lần']++;
            } else {
                $buckets['4+ lần']++;
            }
        }

        return collect($buckets)->map(fn (int $c, string $label) => [
            'label' => $label,
            'count' => $c,
            'pct' => (int) round($c / $total * 100),
        ])->values()->all();
    }

    /**
     * Pageviews bucketed by hour-of-day (0-23), computed in PHP rather
     * than a driver-specific SQL HOUR()/strftime() call so it works the
     * same on SQLite (local) and MySQL (production).
     */
    public static function peakHours(\Illuminate\Support\Carbon $since, ?\Illuminate\Support\Carbon $until = null): array
    {
        $query = PageView::where('created_at', '>=', $since);

        if ($until) {
            $query->where('created_at', '<', $until);
        }

        $byHour = array_fill(0, 24, 0);

        foreach ($query->get(['created_at']) as $row) {
            $byHour[(int) \Illuminate\Support\Carbon::parse($row->created_at)->format('G')]++;
        }

        $max = max(1, max($byHour));

        return collect($byHour)->map(fn (int $count, int $hour) => [
            'hour' => $hour,
            'count' => $count,
            'pct' => (int) round($count / $max * 100),
        ])->values()->all();
    }

    public static function osBreakdown(\Illuminate\Support\Carbon $since, int $limit = 6): \Illuminate\Support\Collection
    {
        return self::columnBreakdown('os', $since, $limit);
    }

    public static function browserBreakdown(\Illuminate\Support\Carbon $since, int $limit = 6): \Illuminate\Support\Collection
    {
        return self::columnBreakdown('browser', $since, $limit);
    }

    public static function languageBreakdown(\Illuminate\Support\Carbon $since, int $limit = 6): \Illuminate\Support\Collection
    {
        return self::columnBreakdown('language', $since, $limit);
    }

    private static function columnBreakdown(string $column, \Illuminate\Support\Carbon $since, int $limit): \Illuminate\Support\Collection
    {
        $counts = PageView::where('created_at', '>=', $since)
            ->whereNotNull($column)
            ->select($column, DB::raw('count(*) as views'))
            ->groupBy($column)
            ->orderByDesc('views')
            ->limit($limit)
            ->pluck('views', $column);

        $total = max(1, $counts->sum());

        return $counts->map(fn (int $views, string $key) => [
            'label' => $key,
            'views' => $views,
            'pct' => (int) round($views / $total * 100),
        ])->values();
    }

    /**
     * Weekly cohort retention: for each week's first-time visitors, what
     * % came back in each of the following weeks. Real computation from
     * `page_views` — no synthetic data. Capped at 4 weeks of look-ahead
     * per cohort so a cohort near "today" just shows fewer columns.
     */
    public static function weeklyCohortRetention(int $weeks = 8): array
    {
        $windowStart = now()->startOfWeek()->subWeeks($weeks - 1);

        $firstSeen = PageView::where('created_at', '>=', $windowStart)
            ->whereNotNull('visitor_id')
            ->select('visitor_id', DB::raw('MIN(created_at) as first_seen'))
            ->groupBy('visitor_id')
            ->pluck('first_seen', 'visitor_id');

        if ($firstSeen->isEmpty()) {
            return [];
        }

        $activity = PageView::where('created_at', '>=', $windowStart)
            ->whereIn('visitor_id', $firstSeen->keys())
            ->get(['visitor_id', 'created_at']);

        $activityWeeksByVisitor = $activity->groupBy('visitor_id')
            ->map(fn ($rows) => $rows->map(
                fn ($r) => \Illuminate\Support\Carbon::parse($r->created_at)->startOfWeek()->toDateString()
            )->unique());

        $visitorCohortWeek = $firstSeen->map(
            fn ($ts) => \Illuminate\Support\Carbon::parse($ts)->startOfWeek()->toDateString()
        );

        $currentWeekStart = now()->startOfWeek();
        $cohorts = [];

        foreach (range(0, $weeks - 1) as $i) {
            $cohortWeek = $windowStart->copy()->addWeeks($i);

            if ($cohortWeek->gt($currentWeekStart)) {
                break;
            }

            $cohortWeekStr = $cohortWeek->toDateString();
            $cohortVisitors = $visitorCohortWeek->filter(fn (string $w) => $w === $cohortWeekStr)->keys();
            $size = $cohortVisitors->count();

            if ($size === 0) {
                continue;
            }

            $maxOffset = (int) $cohortWeek->diffInWeeks($currentWeekStart);
            $retention = [];

            foreach (range(0, min(4, $maxOffset)) as $offset) {
                $targetWeek = $cohortWeek->copy()->addWeeks($offset)->toDateString();
                $retained = $cohortVisitors->filter(
                    fn (string $vid) => $activityWeeksByVisitor->get($vid, collect())->contains($targetWeek)
                )->count();

                $retention[$offset] = [
                    'pct' => (int) round($retained / $size * 100),
                    'count' => $retained,
                ];
            }

            $cohorts[] = [
                'week' => $cohortWeek->format('d/m'),
                'size' => $size,
                'retention' => $retention,
            ];
        }

        return $cohorts;
    }

    /**
     * Buckets a raw referrer_host into one of 4 marketing channels using
     * simple domain pattern matching — no ad-platform integration, so this
     * is the honest ceiling for automatic channel grouping here.
     */
    public static function classifyChannel(?string $referrerHost): string
    {
        if (! $referrerHost) {
            return 'Trực tiếp';
        }

        $host = mb_strtolower($referrerHost);

        foreach (self::SEARCH_ENGINE_HOSTS as $needle) {
            if (str_contains($host, $needle)) {
                return 'Tìm kiếm tự nhiên';
            }
        }

        foreach (self::SOCIAL_HOSTS as $needle) {
            if (str_contains($host, $needle)) {
                return 'Mạng xã hội';
            }
        }

        return 'Giới thiệu';
    }

    public static function channelBreakdown(\Illuminate\Support\Carbon $since): \Illuminate\Support\Collection
    {
        $counts = PageView::where('created_at', '>=', $since)
            ->select('referrer_host', DB::raw('count(*) as views'))
            ->groupBy('referrer_host')
            ->pluck('views', 'referrer_host');

        $byChannel = [];
        foreach ($counts as $host => $views) {
            $channel = self::classifyChannel($host);
            $byChannel[$channel] = ($byChannel[$channel] ?? 0) + $views;
        }

        $total = max(1, array_sum($byChannel));

        return collect(self::CHANNEL_ORDER)->map(fn (string $channel) => [
            'channel' => $channel,
            'views' => $byChannel[$channel] ?? 0,
            'pct' => (int) round(($byChannel[$channel] ?? 0) / $total * 100),
        ]);
    }

    /**
     * Landing-page performance per UTM campaign — which page a campaign's
     * traffic actually lands on and how much of it, all-time (campaigns
     * are typically short-lived, so no default time bound here).
     */
    public static function campaignPerformance(int $limit = 5): \Illuminate\Support\Collection
    {
        $rows = PageView::whereNotNull('utm_campaign')
            ->select('utm_campaign', 'path', DB::raw('count(*) as views'))
            ->groupBy('utm_campaign', 'path')
            ->orderByDesc('views')
            ->get();

        return $rows->groupBy('utm_campaign')->map(function (\Illuminate\Support\Collection $rows, string $campaign) {
            $top = $rows->sortByDesc('views')->first();

            return [
                'campaign' => $campaign,
                'views' => (int) $rows->sum('views'),
                'topPage' => $top->path,
                'topPageViews' => (int) $top->views,
            ];
        })->sortByDesc('views')->take($limit)->values();
    }
}
