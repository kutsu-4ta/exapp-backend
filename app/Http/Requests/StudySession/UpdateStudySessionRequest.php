<?php

namespace App\Http\Requests\StudySession;

use App\Enums\Material;
use App\Enums\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudySessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subjectId' => ['required', 'integer', Rule::exists('subjects', 'id')->where('user_id', $this->user()->id)],
            'timeSlot' => ['required', 'string', Rule::enum(TimeSlot::class)],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'material' => ['required', 'string', Rule::enum(Material::class)],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
