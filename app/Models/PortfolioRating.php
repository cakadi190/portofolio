<?php

namespace App\Models;

use Database\Factories\PortfolioRatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioRating extends Model
{
    /** @use HasFactory<PortfolioRatingFactory> */
    use HasFactory;

    protected $fillable = [
        'portfolio_id', 'reviewer_name', 'reviewer_email', 'reviewer_company', 'title', 'rating', 'comment', 'is_approved',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
