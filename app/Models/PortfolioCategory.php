<?php

namespace App\Models;

use Database\Factories\PortfolioCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PortfolioCategory extends Model
{
    /** @use HasFactory<PortfolioCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'color'];

    /**
     * @return BelongsToMany<Portfolio, $this>
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'portfolio_category_portfolio');
    }
}
