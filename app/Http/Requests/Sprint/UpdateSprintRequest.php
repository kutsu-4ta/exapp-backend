<?php

namespace App\Http\Requests\Sprint;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSprintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:100'],
            'goal'      => ['nullable', 'string', 'max:500'],
            'startDate' => ['sometimes', 'date'],
            'endDate'   => ['sometimes', 'date', 'after_or_equal:startDate'],
        ];
    }
}
