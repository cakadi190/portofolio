<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\AutoGenerateSlug;
use App\Traits\Models\HasEnglishContent;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use AutoGenerateSlug, HasEnglishContent, HasFactory, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['name', 'short_desc', 'description'];

    protected $fillable = [
        'name', 'slug', 'image', 'short_desc', 'description', 'demo_link', 'source_code', 'is_private',
        'name_en', 'short_desc_en', 'description_en',
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
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
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
