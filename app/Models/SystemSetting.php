<?php

namespace App\Models;

use App\Enums\SystemSettingGroup;
use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An administrator-managed runtime setting stored as an encrypted key/value pair.
 *
 * Read settings through SystemSettingService; direct queries are reserved for
 * administration and seeding. The managed keys are defined by
 * {@see SystemSettingGroup::fields()}.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value Encrypted.
 * @property bool $is_active
 */
#[Fillable(['key', 'value', 'is_active'])]
class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    /**
     * Key of the setting holding the inbox that receives contact form messages.
     */
    public const string CONTACT_RECIPIENT_EMAIL = 'contact_recipient_email';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
