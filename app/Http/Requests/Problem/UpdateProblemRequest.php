<?php

namespace App\Http\Requests\Problem;

use App\Enums\FailureType;
use App\Enums\Material;
use App\Enums\Proficiency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProblemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subjectId' => ['required', 'integer', Rule::exists('subjects', 'id')->where('user_id', $this->user()->id)],
            'material' => ['required', 'string', Rule::enum(Material::class)],
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
