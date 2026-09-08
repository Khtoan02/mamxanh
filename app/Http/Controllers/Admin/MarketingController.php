<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PageView;
use App\Models\Post;
use App\Support\TrafficAnalytics;
use Illuminate\Support\Collection;

class MarketingController extends Controller
{
    public function __invoke()
    {
        return view('admin.marketing', [
            'contentPerformance' => $this->contentPerformance(),
            'leadSources' => $this->leadSources(),
            'lastTouchLeadSources' => $this->lastTouchLeadSources(),
            'channels' => TrafficAnalytics::channelBreakdown(now()->subDays(30)),
            'channelConversion' => $this->channelConversion(),
            'campaigns' => TrafficAnalytics::campaignPerformance(),
            'seoChecks' => (new SiteHealthController)->seoChecks(),
        ]);
    }

    /**
     * Attributes each pageview in the last 30 days to a post type by
     * matching its path back to a published post's slug — archive/home
     * paths (/, /blog, /dich-vu...) simply don't match anything and are
     * excluded, which is correct: this is about individual content pages.
     */
    public function contentPerformance()
    {
        $since = now()->subDays(30);

        $pathToType = Post::published()->get(['post_type', 'slug'])
            ->mapWithKeys(fn (Post $post) => ['/'.$post->slug => $post->post_type]);

        $viewsByPath = PageView::where('created_at', '>=', $since)
            ->selectRaw('path, count(*) as views')
            ->groupBy('path')
            ->pluck('views', 'path');

        // Plain array here, not a Collection — `$collection[$key]['views'] +=`
        // silently no-ops on a Collection (ArrayAccess returns by value, not
        // by reference), so the mutation loop needs a real array underneath.
        $byType = [];
        foreach (get_post_types() as $slug => $type) {
            $byType[$slug] = ['label' => $type['label'], 'views' => 0];
        }

        foreach ($viewsByPath as $path => $views) {
            $type = $pathToType[$path] ?? null;
            if ($type && isset($byType[$type])) {
                $byType[$type]['views'] += $views;
            }
        }

        $total = max(1, array_sum(array_column($byType, 'views')));

        return collect($byType)->map(fn (array $row) => [
            ...$row,
            'pct' => (int) round($row['views'] / $total * 100),
        ])->sortByDesc('views')->values();
    }

    /**
     * First-touch attribution: which channel brought in the visitor who
     * eventually submitted this lead, not just "what referred this pageview"
     * (that's Analytics' Top Referrers — this is specifically about leads).
     */
    public function leadSources()
    {
        $leads = ContactMessage::get(['visitor_id']);

        $firstTouchByVisitor = $this->touchByVisitor($leads->pluck('visitor_id'), 'first');

        $bySource = $leads
            ->map(fn (ContactMessage $lead) => ($lead->visitor_id ? $firstTouchByVisitor->get($lead->visitor_id) : null) ?: 'Trực tiếp / Không rõ')
            ->countBy()
            ->sortDesc();

        return $this->withPct($bySource);
    }

    /**
     * Last-touch attribution: the referrer of the visitor's last pageview
     * *before* they actually submitted the form — the touchpoint that
     * immediately preceded conversion, as opposed to first-touch's "how
     * did they discover us at all."
     */
    public function lastTouchLeadSources()
    {
        $leads = ContactMessage::whereNotNull('visitor_id')->get(['visitor_id', 'created_at']);
        $lastTouchByVisitor = $this->touchByVisitor($leads->pluck('visitor_id'), 'last', $leads);

        $bySource = $leads
            ->map(fn (ContactMessage $lead) => $lastTouchByVisitor->get($lead->visitor_id) ?: 'Trực tiếp / Không rõ')
            ->countBy()
            ->sortDesc();

        return $this->withPct($bySource);
    }

    /**
     * How many first-touch visitors per channel actually became a lead —
     * ties the channel grouping (Direct/Organic/Social/Referral) to a real
     * conversion outcome instead of just a traffic split.
     */
    public function channelConversion(): Collection
    {
        $visitorIds = PageView::whereNotNull('visitor_id')->distinct()->pluck('visitor_id');
        $firstHostByVisitor = $this->touchByVisitor($visitorIds, 'first');

        $visitorsByChannel = $firstHostByVisitor->countBy(fn (?string $host) => TrafficAnalytics::classifyChannel($host));

        $leads = ContactMessage::get(['visitor_id']);
        $leadsByChannel = $leads->countBy(function (ContactMessage $lead) use ($firstHostByVisitor) {
            $host = $lead->visitor_id ? $firstHostByVisitor->get($lead->visitor_id) : null;

            return TrafficAnalytics::classifyChannel($host);
        });

        return collect(['Trực tiếp', 'Tìm kiếm tự nhiên', 'Mạng xã hội', 'Giới thiệu'])->map(function (string $channel) use ($visitorsByChannel, $leadsByChannel) {
            $visitors = $visitorsByChannel[$channel] ?? 0;
            $leadCount = $leadsByChannel[$channel] ?? 0;

            return [
                'channel' => $channel,
                'visitors' => $visitors,
                'leads' => $leadCount,
                'rate' => $visitors > 0 ? round($leadCount / $visitors * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Shared visitor -> referrer_host lookup for first-touch / last-touch
     * attribution, one query instead of N. When $leads is passed, "last"
     * means the last pageview at-or-before that specific lead's created_at
     * (the true pre-conversion touchpoint); without it, "last" just means
     * the visitor's most recent pageview overall.
     */
    private function touchByVisitor(Collection $visitorIds, string $which, ?Collection $leads = null): Collection
    {
        $ids = $visitorIds->filter()->unique();

        $viewsByVisitor = PageView::whereIn('visitor_id', $ids)
            ->orderBy('created_at')
            ->get(['visitor_id', 'referrer_host', 'created_at'])
            ->groupBy('visitor_id');

        $leadByVisitor = $leads?->keyBy('visitor_id');

        return $viewsByVisitor->map(function (Collection $views, string $visitorId) use ($which, $leadByVisitor) {
            if ($which === 'first') {
                $view = $views->first();
            } elseif ($leadByVisitor) {
                $cutoff = $leadByVisitor->get($visitorId)?->created_at;
                $view = $views->filter(fn ($v) => ! $cutoff || $v->created_at <= $cutoff)->last() ?? $views->last();
            } else {
                $view = $views->last();
            }

            return $view->referrer_host;
        });
    }

    private function withPct(Collection $counts): Collection
    {
        $total = max(1, $counts->sum());

        return $counts->map(fn (int $count, string $source) => [
            'source' => $source,
            'count' => $count,
            'pct' => (int) round($count / $total * 100),
        ])->values();
    }
}
