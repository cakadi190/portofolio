<?php

namespace App\Services;

use App\Enums\BlogCommentStatus;
use App\Enums\UserRole;
use App\Models\Award;
use App\Models\BlogComment;
use App\Models\Career;
use App\Models\Certification;
use App\Models\CoffeePlace;
use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Media;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\PortfolioRating;
use App\Models\Post;
use App\Models\Service;
use App\Models\SpeakingEngagement;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Builds the numbers shown on the admin dashboard. Blog figures are for every
 * staff member; site-wide figures and system info are admin-only.
 */
class DashboardService
{
    /**
     * @return array{
     *     blog: array<string, mixed>,
     *     site: array<string, mixed>|null,
     *     system: array<string, mixed>|null
     * }
     */
    public function summary(User $user): array
    {
        return [
            'blog' => $this->blog($user),
            'site' => $user->isAdmin() ? $this->site() : null,
            'system' => $user->isAdmin() ? $this->system() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blog(User $user): array
    {
        $posts = fn () => Post::query()->manageableBy($user);

        return [
            'posts_total' => $posts()->count(),
            'posts_published' => $posts()->published()->count(),
            'posts_scheduled' => $posts()->where('is_published', true)->where('published_at', '>', now())->count(),
            'posts_draft' => $posts()->where('is_published', false)->count(),
            'tags_total' => Tag::count(),
            'comments_pending' => BlogComment::where('status', BlogCommentStatus::Pending)->count(),
            'comments_total' => BlogComment::count(),
            'monthly' => $this->monthlyBlogActivity($user),
            'recent_posts' => $posts()->with('author:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'user_id', 'title', 'is_published', 'published_at', 'created_at'])
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'author' => $post->author?->name,
                    'is_published' => $post->is_published,
                    'is_scheduled' => $post->isScheduled(),
                    'date' => ($post->published_at ?? $post->created_at)?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function site(): array
    {
        $now = now()->format('Y-m-d H:i:s');

        return [
            'counts' => [
                'portfolios' => Portfolio::count(),
                'services' => Service::count(),
                'technologies' => Technology::count(),
                'awards' => Award::count(),
                'certifications' => Certification::count(),
                'educations' => Education::count(),
                'careers' => Career::count(),
                'organizations' => Organization::count(),
                'coffee_places' => CoffeePlace::count(),
                'speaking_engagements' => SpeakingEngagement::count(),
                'media' => Media::count(),
            ],
            'media_size' => (int) Media::sum('size'),
            'users' => [
                'total' => User::count(),
                'admin' => User::where('account_type', UserRole::Admin)->count(),
                'redaktur' => User::where('account_type', UserRole::Redaktur)->count(),
                'user' => User::where('account_type', UserRole::User)->count(),
            ],
            'inbox' => [
                'unread' => ContactMessage::whereNull('read_at')->count(),
                'total' => ContactMessage::count(),
                'latest' => ContactMessage::latest()
                    ->limit(5)
                    ->get(['id', 'name', 'email', 'reason', 'read_at', 'created_at'])
                    ->map(fn (ContactMessage $message): array => [
                        'id' => $message->id,
                        'name' => $message->name,
                        'email' => $message->email,
                        'reason' => $message->reason->label(),
                        'is_read' => $message->read_at !== null,
                        'date' => $message->created_at?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'traffic' => $this->traffic(),
            'ratings_pending' => PortfolioRating::where('is_approved', false)->count(),
            'upcoming_speaking' => SpeakingEngagement::published()
                ->where('starts_at', '>=', $now)
                ->orderBy('starts_at')
                ->limit(5)
                ->get(['id', 'title', 'organizer', 'starts_at'])
                ->map(fn (SpeakingEngagement $engagement): array => [
                    'id' => $engagement->id,
                    'title' => $engagement->title,
                    'organizer' => $engagement->organizer,
                    'starts_at' => $engagement->starts_at->format('Y-m-d H:i'),
                ])
                ->all(),
        ];
    }

    /**
     * Posts written and comments received per month for the last 6 months.
     *
     * @return list<array{month: string, posts: int, comments: int}>
     */
    private function monthlyBlogActivity(User $user): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $countByMonth = fn (Builder $query): array => $query->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn ($date): string => $date->format('Y-m'))
            ->all();

        $posts = $countByMonth(Post::query()->manageableBy($user));
        $comments = $countByMonth(BlogComment::query());

        return collect(range(0, 5))
            ->map(function (int $offset) use ($start, $posts, $comments): array {
                $month = $start->copy()->addMonths($offset)->format('Y-m');

                return ['month' => $month, 'posts' => $posts[$month] ?? 0, 'comments' => $comments[$month] ?? 0];
            })
            ->all();
    }

    /**
     * Human page views for the last 14 days (zero-filled) plus the top pages.
     *
     * @return array{series: list<array{day: string, views: int}>, total: int, today: int, previous_total: int, top_pages: list<array{path: string, views: int}>}
     */
    private function traffic(): array
    {
        $days = 14;
        $from = now()->subDays($days - 1)->toDateString();
        $previousFrom = now()->subDays($days * 2 - 1)->toDateString();

        $perDay = DB::table('visitor_daily_stats')
            ->where('is_bot', false)
            ->where('day', '>=', $from)
            ->groupBy('day')
            ->selectRaw('day, sum(page_views) as views')
            ->pluck('views', 'day');

        $series = collect(range($days - 1, 0))
            ->map(fn (int $ago): array => [
                'day' => now()->subDays($ago)->toDateString(),
                'views' => (int) ($perDay[now()->subDays($ago)->toDateString()] ?? 0),
            ])
            ->all();

        return [
            'series' => $series,
            'total' => array_sum(array_column($series, 'views')),
            'today' => end($series)['views'],
            'previous_total' => (int) DB::table('visitor_daily_stats')
                ->where('is_bot', false)
                ->where('day', '>=', $previousFrom)
                ->where('day', '<', $from)
                ->sum('page_views'),
            'top_pages' => DB::table('visitor_daily_stats')
                ->where('is_bot', false)
                ->where('day', '>=', $from)
                ->groupBy('path')
                ->selectRaw('path, sum(page_views) as views')
                ->orderByDesc('views')
                ->limit(5)
                ->get()
                ->map(fn (object $row): array => ['path' => $row->path, 'views' => (int) $row->views])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function system(): array
    {
        $connection = DB::connection();
        $databasePath = $connection->getDriverName() === 'sqlite' ? $connection->getDatabaseName() : null;

        return [
            'app_name' => config('app.name'),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'database_driver' => $connection->getDriverName(),
            'database_size' => $databasePath !== null && is_file($databasePath) ? (int) File::size($databasePath) : null,
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'mail_mailer' => config('mail.default'),
            'jobs_pending' => $this->tableCount('jobs'),
            'jobs_failed' => $this->tableCount('failed_jobs'),
        ];
    }

    private function tableCount(string $table): ?int
    {
        return DB::getSchemaBuilder()->hasTable($table) ? DB::table($table)->count() : null;
    }
}
