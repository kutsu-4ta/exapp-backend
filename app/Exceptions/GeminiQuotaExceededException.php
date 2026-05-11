<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiQuotaExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Gemini quota exceeded (RESOURCE_EXHAUSTED)');
    }
}
