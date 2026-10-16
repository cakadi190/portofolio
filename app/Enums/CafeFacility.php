<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum CafeFacility: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Outdoor = 'outdoor';
    case Indoor = 'indoor';
    case AirConditioner = 'ac';
    case WorkFromCafe = 'wfc';
    case Hangout = 'nongkrong';
    case Smoking = 'smoking';
    case NonSmoking = 'non_smoking';
    case Musholla = 'musholla';
    case PowerOutlet = 'power_outlet';
    case Parking = 'parking';
    case Toilet = 'toilet';
    case LiveMusic = 'live_music';
    case MeetingRoom = 'meeting_room';
    case Food = 'food';
    case Delivery = 'delivery';
    case Open24Hours = 'open_24_hours';
}
