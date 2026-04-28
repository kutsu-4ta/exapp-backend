<?php

namespace App\Enums;

enum TimeSlot: string
{
    case Morning = 'morning';
    case Lunch = 'lunch';
    case Commute = 'commute';
    case Night = 'night';
}
