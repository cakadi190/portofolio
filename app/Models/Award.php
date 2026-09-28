<?php

namespace App\Models;

use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Award extends Model
{
    /** @use HasFactory<AwardFactory> */
    use HasFactory;

    protected $fillable = ['event_name', 'title', 'year', 'rank', 'awarded_at'];

    protected $appends = ['icon'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rank' => 'integer',
            'awarded_at' => 'date',
        ];
    }

    /**
     * Iconify icon id derived from the award: a trophy for podium places, a
     * medal for other ranks, and a certificate for unranked or certification entries.
     *
     * @return Attribute<string, never>
     */
    protected function icon(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->rank === null || Str::contains(Str::lower($this->title), ['certif', 'sertif'])) {
                return 'mdi:certificate';
            }

            return $this->rank <= 3 ? 'fa6-solid:trophy' : 'fa6-solid:medal';
        });
    }
}
