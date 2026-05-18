<?php

namespace App\Http\Requests\Sprint;

use Illuminate\Foundation\Http\FormRequest;

class CompleteSprintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'retrospective' => ['required', 'string'],
        ];
    }
}
