<?php

namespace App\Enums;

enum TicketPriority: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';
}
