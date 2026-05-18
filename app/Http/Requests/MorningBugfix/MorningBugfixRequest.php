<?php

namespace App\Http\Requests\MorningBugfix;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Enums\Rank;
use App\UseCases\MorningBugfix\BugfixFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MorningBugfixRequest extends FormRequest
{
    public function rules(): array
    {
        $failureTypes  = array_column(FailureType::cases(), 'value');
        $proficiencies = array_column(Proficiency::cases(), 'value');

        return [
            'date'            => ['sometimes', 'nullable', 'date'],

            'failureTypes'     => ['sometimes', 'array'],
            'failureType.*'   => [Rule::in($failureTypes)],

            'subCategoryIds'  => ['sometimes', 'array'],
            'subCategoryIds.*' => ['integer', 'exists:sub_categories,id'],

            'touchedOrder'    => ['sometimes', 'nullable', Rule::in(['recent', 'old'])],
            'limit'           => ['sometimes', 'integer', 'min:1', 'max:' . BugfixFilter::MAX_LIMIT],
            'proficiency'     => ['sometimes', 'array'],
            'proficiency.*'   => ['string', Rule::in($proficiencies)],

            'ranks'           => ['sometimes', 'array'],
            'ranks.*'         => ['string', Rule::enum(Rank::class)],
        ];
    }
}
