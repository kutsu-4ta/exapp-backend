<?php

namespace App\Http\Requests\DegBugfix;

use Illuminate\Foundation\Http\FormRequest;

class DegBugfixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'nullable', 'string'],
            'limit'   => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }
}
