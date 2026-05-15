<?php

namespace App\Http\Requests\ExamSession;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuickScoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'    => ['required', 'string'],
            'examYear'   => ['required', 'string', 'max:10'],
            'totalScore' => ['required', 'integer', 'min:0', 'max:100'],
            'pureScore'  => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
