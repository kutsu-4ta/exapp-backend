<?php

namespace App\Http\Requests\DegBugfix;

use App\Enums\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DegBugfixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'nullable', 'string'],
            'limit'   => ['sometimes', 'integer', 'min:1', 'max:20'],
            'ranks'   => ['sometimes', 'array'],
            'ranks.*' => ['string', Rule::enum(Rank::class)],
        ];
    }
}
