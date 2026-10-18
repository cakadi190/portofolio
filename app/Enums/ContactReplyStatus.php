<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContactReplyStatus: string
{
    use HasLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
