<?php

namespace App\Enums;

enum TicketSource: string
{
    case WrongAnswer = 'wrong_answer';
    case LoadMap = 'load_map';
    case Review = 'review';
    case Manual = 'manual';
}
