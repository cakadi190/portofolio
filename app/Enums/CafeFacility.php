<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum CafeFacility: string
{
    use HasEnumOptions, HasEnumValues;

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

    public function label(): string
    {
        return match ($this) {
            self::Outdoor => 'Outdoor',
            self::Indoor => 'Indoor',
            self::AirConditioner => 'AC',
            self::WorkFromCafe => 'WFC (Work From Cafe)',
            self::Hangout => 'Nongkrong',
            self::Smoking => 'Smoking Area',
            self::NonSmoking => 'Non-Smoking Area',
            self::Musholla => 'Musholla',
            self::PowerOutlet => 'Colokan Listrik',
            self::Parking => 'Area Parkir',
            self::Toilet => 'Toilet',
            self::LiveMusic => 'Live Music',
            self::MeetingRoom => 'Ruang Meeting',
            self::Food => 'Makanan Berat',
            self::Delivery => 'Pesan Antar',
            self::Open24Hours => 'Buka 24 Jam',
        };
    }
}
