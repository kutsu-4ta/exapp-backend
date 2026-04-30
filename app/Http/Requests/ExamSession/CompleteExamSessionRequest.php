<?php

namespace App\Http\Requests\ExamSession;

use App\Enums\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteExamSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', Rule::exists('subjects', 'name')],
            'examYear' => ['required', 'string', 'max:10'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.sortOrder' => ['required', 'integer', 'min:0'],
            'questions.*.displayId' => ['required', 'string', 'max:100'],
            'questions.*.isSub' => ['required', 'boolean'],
            'questions.*.hasChildren' => ['required', 'boolean'],
            'questions.*.rank' => ['required', 'string', Rule::enum(Rank::class)],
            'questions.*.myAnswer' => ['nullable', 'string', 'max:255'],
            'questions.*.isCorrect' => ['nullable', 'boolean'],
            'questions.*.isDoubtful' => ['required', 'boolean'],
            'questions.*.point' => ['required', 'integer', 'min:0'],
            'questions.*.note' => ['nullable', 'string', 'max:2000'],
            'questions.*.answeredTimeMs' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
