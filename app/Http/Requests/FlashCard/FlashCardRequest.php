<?php

namespace App\Http\Requests\FlashCard;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\UseCases\FlashCard\FlashCardFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlashCardRequest extends FormRequest
{
    public function rules(): array
    {
        $failureTypes  = array_column(FailureType::cases(), 'value');
        $proficiencies = array_column(Proficiency::cases(), 'value');

        return [
            'subject'          => ['required', 'string'],

            'failureTypes'     => ['sometimes', 'array'],
            'failureTypes.*'   => ['string', Rule::in($failureTypes)],

            'subCategoryIds'   => ['sometimes', 'array'],
            'subCategoryIds.*' => ['integer', 'exists:sub_categories,id'],

            'touchedOrder'     => ['sometimes', 'nullable', Rule::in(['recent', 'old'])],
            'limit'            => ['sometimes', 'integer', 'min:1', 'max:' . FlashCardFilter::MAX_LIMIT],

            'proficiency'      => ['sometimes', 'array'],
            'proficiency.*'    => ['string', Rule::in($proficiencies)],

            'formulaOnly'      => ['sometimes', 'boolean'],
        ];
    }
}
