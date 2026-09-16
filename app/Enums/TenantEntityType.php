<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TenantEntityType: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Individual = 'individual';
    case Company = 'company';
    case Community = 'community';
    case Government = 'government';

    /**
     * @return array<int, TenantDocumentType>
     */
    public function requiredDocuments(): array
    {
        return match ($this) {
            self::Individual => [TenantDocumentType::Ktp, TenantDocumentType::Npwp],
            self::Company => [TenantDocumentType::Ktp, TenantDocumentType::Npwp, TenantDocumentType::Nib, TenantDocumentType::Akta],
            self::Community => [TenantDocumentType::Ktp, TenantDocumentType::SuratKeterangan],
            self::Government => [TenantDocumentType::SuratKeterangan],
        };
    }
}
