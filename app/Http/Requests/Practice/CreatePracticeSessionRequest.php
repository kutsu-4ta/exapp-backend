<?php

namespace App\Http\Requests\Practice;

use Illuminate\Foundation\Http\FormRequest;

class CreatePracticeSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'        => ['required', 'string'],
            'date'           => ['required', 'date_format:Y-m-d'],
            'totalElapsedMs' => ['required', 'integer', 'min:0'],
        ];
    }
}
