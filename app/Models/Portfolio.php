<?php

namespace App\Models;

use App\Traits\Models\AutoGenerateSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    use AutoGenerateSlug;

    protected $fillable = [
        'name', 'slug', 'image', 'short_desc', 'description', 'demo_link', 'source_code', 'is_private',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Technology, $this>
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class);
    }

    /**
     * @return BelongsToMany<PortfolioCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PortfolioCategory::class, 'portfolio_category_portfolio');
    }

    /**
     * @return BelongsToMany<Career, $this>
     */
    public function careers(): BelongsToMany
    {
        return $this->belongsToMany(Career::class);
    }

    /**
     * @return HasMany<PortfolioRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(PortfolioRating::class);
    }

    /**
     * @return HasMany<PortfolioGallery, $this>
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(PortfolioGallery::class);
    }
}
