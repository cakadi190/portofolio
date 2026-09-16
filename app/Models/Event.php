<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Traits\Models\AutoGenerateSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Event extends Model
{
    use AutoGenerateSlug, SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'tenant_id', 'event_category_id', 'title', 'slug', 'excerpt', 'description', 'rules',
        'keywords', 'start', 'end', 'is_always_open', 'location', 'city', 'place', 'map_link',
        'whatsapp_link', 'contact_person', 'featured', 'status', 'image_url', 'venue_layout_url',
        'merchandise',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'start' => 'datetime',
            'end' => 'datetime',
            'is_always_open' => 'boolean',
            'featured' => 'boolean',
            'keywords' => 'array',
            'merchandise' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'username');
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Filter events into upcoming/ongoing/past buckets, computed from
     * `start`/`end`/`is_always_open` rather than a stored status.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        $now = Carbon::now();

        return match ($status) {
            'upcoming' => $query->where('start', '>', $now),
            'ongoing' => $query->where('start', '<=', $now)
                ->where(fn (Builder $query) => $query->where('is_always_open', true)
                    ->orWhereNull('end')
                    ->orWhere('end', '>=', $now)),
            'past' => $query->where('is_always_open', false)
                ->whereNotNull('end')
                ->where('end', '<', $now),
            default => $query,
        };
    }
}
