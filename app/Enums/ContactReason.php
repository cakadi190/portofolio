<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum ContactReason: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Connecting = 'connecting';
    case ProjectCollaboration = 'project_collaboration';
    case General = 'general';
    case Correction = 'correction';
    case Others = 'others';
}
