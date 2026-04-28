<?php

namespace App\Enums;

enum Proficiency: string
{
    case Correct = '○';
    case Partial = '△';
    case Incorrect = '×';
}
