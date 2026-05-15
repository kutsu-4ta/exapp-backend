<?php

namespace App\Http\Requests\Problem;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProblemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string'],
            'materialId' => ['nullable', 'integer', 'exists:materials,id'],
            'materialName' => ['nullable', 'string', 'max:255'],
            'subCategory' => ['nullable', 'string', 'max:255'],
            'questionRef' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'proficiency' => ['required', 'string', Rule::enum(Proficiency::class)],
            'failureTypes' => ['required', 'array'],
            'failureTypes.*' => ['string', Rule::enum(FailureType::class)],
            'isGoodQuestion' => ['required', 'boolean'],
            'isFormula'      => ['sometimes', 'boolean'],
            'solvedAt' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->materialId === null && ($this->materialName === null || $this->materialName === '')) {
                $v->errors()->add('material', 'materialId または materialName のどちらか一方は必須です。');
            }
        });
    }
}
