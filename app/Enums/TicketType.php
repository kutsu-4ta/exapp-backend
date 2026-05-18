<?php

namespace App\Enums;

enum TicketType: string
{
    case Knowledge     = 'knowledge';
    case Practice      = 'practice';
    case Understanding = 'understanding';
    case Memorization  = 'memorization';
}
