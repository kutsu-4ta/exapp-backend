<?php

namespace App\Http\Requests\SubCategory;

use App\Enums\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string'],
            'name'    => ['required', 'string', 'max:255'],
            'rank'    => ['sometimes', 'nullable', Rule::enum(Rank::class)],
        ];
    }
}
