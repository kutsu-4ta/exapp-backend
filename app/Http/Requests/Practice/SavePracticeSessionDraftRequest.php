<?php

namespace App\Http\Requests\Practice;

use Illuminate\Foundation\Http\FormRequest;

class SavePracticeSessionDraftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'      => ['required', 'string'],
            'currentIndex' => ['required', 'integer', 'min:1'],
            'log'          => ['required', 'array'],
            'log.*.index'              => ['required', 'integer', 'min:1'],
            'log.*.elapsedMs'          => ['required', 'integer', 'min:0'],
            'log.*.answers'            => ['required', 'array'],
            'log.*.answers.*.answer'     => ['required', 'string'],
            'log.*.answers.*.isDoubtful' => ['required', 'boolean'],
            'log.*.answers.*.note'       => ['nullable', 'string', 'max:2000'],
        ];
    }
}
