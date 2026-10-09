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
            'blog' => $this->blog(),
            'site' => $user->isAdmin() ? $this->site() : null,
            'system' => $user->isAdmin() ? $this->system() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blog(): array
    {
        return [
            'posts_total' => Post::count(),
            'posts_published' => Post::where('is_published', true)->count(),
            'posts_draft' => Post::where('is_published', false)->count(),
            'tags_total' => Tag::count(),
            'comments_pending' => BlogComment::where('status', BlogCommentStatus::Pending)->count(),
            'comments_total' => BlogComment::count(),
            'recent_posts' => Post::with('author:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'user_id', 'title', 'is_published', 'published_at', 'created_at'])
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'author' => $post->author?->name,
                    'is_published' => $post->is_published,
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
