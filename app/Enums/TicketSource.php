<?php

namespace App\Enums;

enum TicketSource: string
{
    case WrongAnswer = 'wrong_answer';
    case MockExam    = 'mock_exam';
    case Review      = 'review';
    case Manual      = 'manual';
}
