<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TransactionStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case Timeout = 'timeout';

    /**
     * Maps a gateway-reported status string (DOKU, Paynext, Pakasir, Tripay, ...)
     * onto the canonical set. Each gateway adapter is responsible for translating
     * its own vocabulary before calling this.
     */
    public static function fromGatewayStatus(string $status): self
    {
        return match (strtoupper($status)) {
            'SUCCESS', 'PAID', 'SETTLEMENT', 'CAPTURE' => self::Success,
            'FAILED', 'DENY', 'CANCEL' => self::Failed,
            'EXPIRED', 'EXPIRE' => self::Expired,
            'REFUNDED', 'REFUND' => self::Refunded,
            'TIMEOUT' => self::Timeout,
            default => self::Pending,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Success->value => 'success',
            self::Failed->value => 'danger',
            self::Expired->value => 'secondary',
            self::Refunded->value => 'info',
            self::Timeout->value => 'dark',
        ];
    }
}
