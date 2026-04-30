<?php

namespace App\Http\Requests\ExamSession;

use App\Enums\ExamSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', Rule::exists('subjects', 'name')],
            'examYear' => ['sometimes', 'string', 'max:10'],
            'status' => ['sometimes', 'string', Rule::enum(ExamSessionStatus::class)],
        ];
    }
}
