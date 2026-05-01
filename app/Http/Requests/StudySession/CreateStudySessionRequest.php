<?php

namespace App\Http\Requests\StudySession;

use App\Enums\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStudySessionRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user() ?? auth('sanctum')->user();

        return [
            'dailyLogDate' => ['required', 'date_format:Y-m-d'],
            'timeSlot' => ['required', 'string', Rule::enum(TimeSlot::class)],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'subject' => ['required', 'string'],
            'material' => 'required|string',
            'subCategoryId' => [
                'nullable',
                'integer',
                Rule::exists('sub_categories', 'id')->where('user_id', $user->id),
            ],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
