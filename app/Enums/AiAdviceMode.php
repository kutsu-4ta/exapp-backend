<?php

namespace App\Enums;

enum AiAdviceMode: string
{
    case ANALYSIS    = 'ANALYSIS';
    case INSPIRATION = 'INSPIRATION';
    case ANALOGY     = 'ANALOGY';
    case WARNING     = 'WARNING';
}
