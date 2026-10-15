<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\ContactReason;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = ['name', 'email', 'reason', 'message', 'read_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ContactReason::class,
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ContactMessageReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->latest();
    }
}
