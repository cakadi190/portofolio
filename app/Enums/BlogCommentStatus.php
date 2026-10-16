<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum BlogCommentStatus: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
