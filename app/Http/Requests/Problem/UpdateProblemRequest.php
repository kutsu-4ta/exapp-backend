<?php

namespace App\Http\Requests\Problem;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProblemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'     => ['required', 'string'],
            'material'    => ['required', 'string'],
            'subCategory' => ['nullable', 'string', 'max:255'],
            'questionRef' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'proficiency' => ['required', 'string', Rule::enum(Proficiency::class)],
            'failureTypes' => ['required', 'array'],
            'failureTypes.*' => ['string', Rule::enum(FailureType::class)],
            'isGoodQuestion' => ['required', 'boolean'],
            'solvedAt' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
