<?php

namespace App\Models;

use Database\Factories\CertificationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    /** @use HasFactory<CertificationFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'issuer', 'issued_at', 'expires_at', 'credential_id', 'credential_url', 'file',
    ];

    protected $appends = ['is_pdf'];

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
}
