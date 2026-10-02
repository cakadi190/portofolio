<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'disk', 'path', 'name', 'alt', 'mime_type', 'size', 'width', 'height',
    ];

    protected $appends = ['url', 'is_image'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageService::url($this->path));
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isImage(): Attribute
    {
        return Attribute::get(fn (): bool => str_starts_with($this->mime_type, 'image/'));
    }
}
