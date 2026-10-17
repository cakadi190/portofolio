<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Services\IconService;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = ['name', 'icon'];

    /**
     * Resolve picker values to technology ids: an Iconify id (`prefix:name`) is reused
     * or created, a plain id is a technology that already exists.
     *
     * @param  array<int, int|string>  $values
     * @return array<int, int>
     */
    public static function idsFor(array $values): array
    {
        return collect($values)->map(function (int|string $value): int {
            if (! IconService::isValidId((string) $value)) {
                return (int) $value;
            }

            $icon = (string) $value;
            $name = IconService::label($icon);

            $technology = self::query()->where('icon', $icon)->first()
                ?? self::query()->where('name', $name)->first();

            if ($technology === null) {
                return self::query()->create(['name' => $name, 'icon' => $icon])->id;
            }

            if ($technology->icon === null) {
                $technology->update(['icon' => $icon]);
            }

            return $technology->id;
        })->unique()->values()->all();
    }

    /**
     * @return BelongsToMany<Portfolio, $this>
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class);
    }
}
