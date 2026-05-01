<?php

namespace App\Enums;

enum FailureType: string
{
    case MissingDefinition = '定義ミス';
    case WrongApproach = '解法ミス';
    case CalculationError = '計算ミス';
}
