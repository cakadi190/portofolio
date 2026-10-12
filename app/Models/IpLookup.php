<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Cached IP enrichment result. Only fields actually returned by the provider
 * are stored; a failed lookup is recorded with its status and no metadata.
 *
 * @property string $ip_address
 * @property string $status ok|private|failed|rate_limited
 */
#[Fillable(['ip_address', 'provider', 'status', 'country', 'region', 'city', 'timezone', 'asn', 'organization', 'hostname', 'latitude', 'longitude', 'attempts', 'fetched_at', 'expires_at'])]
class IpLookup extends Model
{
    public const string STATUS_OK = 'ok';

    public const string STATUS_PRIVATE = 'private';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_RATE_LIMITED = 'rate_limited';

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
