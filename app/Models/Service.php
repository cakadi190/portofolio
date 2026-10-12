<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\AutoGenerateSlug;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use AutoGenerateSlug, HasFactory, RecordsActivity;

    protected $fillable = ['slug', 'name', 'color', 'image', 'description'];

    /**
     * @return BelongsToMany<Portfolio, $this>
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class);
    }
}
