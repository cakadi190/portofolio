<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\HasEnglishContent;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasEnglishContent, RecordsActivity;

    /** @var list<string> */
    protected array $englishAttributes = ['description'];

    /** Admin lists carry the raw text of every language, for the edit form. */
    protected bool $withTranslations = true;

    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'description_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }
}
