<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A first-party, cookie-identified browser. Not a person: one human may be
 * several visitors, and cleared cookies create a new one.
 */
#[Fillable(['token', 'user_id', 'ip_address', 'user_agent', 'is_bot', 'visits_count', 'page_views_count', 'first_seen_at', 'last_seen_at'])]
class Visitor extends Model
{
    protected function casts(): array
    {
        return ['is_bot' => 'boolean', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }
}
