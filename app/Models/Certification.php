<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Database\Factories\CertificationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certification extends Model
{
    /** @use HasFactory<CertificationFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'title', 'issuer', 'issued_at', 'expires_at', 'credential_id', 'credential_url', 'file',
    ];

    protected $appends = ['is_pdf', 'slug'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'date:Y-m-d',
            'expires_at' => 'date:Y-m-d',
        ];
    }

    /**
     * Whether the uploaded certificate is a PDF rather than an image.
     *
     * @return Attribute<bool, never>
     */
    protected function isPdf(): Attribute
    {
        return Attribute::get(fn (): bool => str_ends_with(strtolower($this->file), '.pdf'));
    }

    /**
     * Public URL segment of the dedicated PDF page: the id keeps it unique and resolvable, the title keeps it readable.
     *
     * @return Attribute<string, never>
     */
    protected function slug(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->getKey().'-'.Str::slug($this->title), '-'));
    }

    /**
     * Find a certification from its public slug (`{id}-{title}`), ignoring a stale or missing title part.
     */
    public static function findBySlug(string $slug): ?self
    {
        return preg_match('/^(\d+)(?:-|$)/', $slug, $match) === 1 ? static::query()->find((int) $match[1]) : null;
    }
}
