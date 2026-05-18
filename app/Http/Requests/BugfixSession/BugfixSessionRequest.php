<?php

namespace App\Http\Requests\BugfixSession;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BugfixSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source'           => ['sometimes', 'in:ai,saved'],
            'subjects'         => ['sometimes', 'array'],
            'subjects.*'       => ['string'],
            'quizMode'         => ['sometimes', 'in:multiple_choice,word_card'],
            'formulaOnly'      => ['sometimes', 'boolean'],
            'failureTypes'     => ['sometimes', 'array'],
            'failureTypes.*'   => ['string', Rule::enum(FailureType::class)],
            'subCategoryIds'   => ['sometimes', 'array'],
            'subCategoryIds.*' => ['integer'],
            'proficiency'      => ['sometimes', 'array'],
            'proficiency.*'    => ['string', Rule::enum(Proficiency::class)],
            'touchedOrder'     => ['sometimes', 'nullable', 'in:recent,old'],
            'limit'            => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }
}
