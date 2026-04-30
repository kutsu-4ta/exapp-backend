<?php

namespace App\Http\Requests\ExamSession;

use Illuminate\Foundation\Http\FormRequest;

class CreateExamSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string'],
            'examYear' => ['required', 'string', 'max:10'],
        ];
    }
}
