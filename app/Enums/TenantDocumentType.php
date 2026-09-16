<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TenantDocumentType: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Ktp = 'ktp';
    case Npwp = 'npwp';
    case Nib = 'nib';
    case Akta = 'akta';
    case SuratKeterangan = 'surat_keterangan';
}
