<?php

namespace App\Models;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDocument extends Model
{
    use HasUlids;

    protected $fillable = ['tenant_id', 'type', 'status', 'file', 'rejection_reason'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TenantDocumentType::class,
            'status' => TenantDocumentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'username');
    }
}
