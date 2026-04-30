<?php

namespace App\Http\Requests\ExamSession;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateExamSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', Rule::exists('subjects', 'name')],
            'examYear' => ['required', 'string', 'max:10'],
        ];
    }
}
