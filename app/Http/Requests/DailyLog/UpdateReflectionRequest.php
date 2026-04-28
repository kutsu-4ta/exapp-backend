<?php

namespace App\Http\Requests\DailyLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReflectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reflection' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
