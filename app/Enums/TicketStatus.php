<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Todo  = 'todo';
    case Doing = 'doing';
    case Done  = 'done';
}
