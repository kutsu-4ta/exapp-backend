<?php

namespace App\Enums;

enum FailureType: string
{
    case MissingDefinition = '定義';
    case WrongApproach = '解法';
    case CalculationError = 'ケアレス';
}
