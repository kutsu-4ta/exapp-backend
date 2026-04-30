<?php

namespace App\Http\Requests\ExamSession;

use App\Enums\ExamSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExamSessionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(ExamSessionStatus::class)],
            'subject' => ['nullable', 'string', Rule::exists('subjects', 'name')],
        ];
    }
}
