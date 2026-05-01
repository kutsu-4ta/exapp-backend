<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nickname'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'occupation'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'goal'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'weakAreas'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'strongAreas'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'interests'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'geminiToken'  => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }
}
