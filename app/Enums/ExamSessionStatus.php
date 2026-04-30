<?php

namespace App\Enums;

enum ExamSessionStatus: string
{
    case InProgress = 'in_progress';
    case Scoring = 'scoring';
    case Completed = 'completed';
}
