<?php

namespace App\Enums;

enum FailureType: string
{
    case MissingDefinition = '定義漏れ';
    case WrongApproach = '解法ミス';
    case CalculationError = '計算ミス';
}
