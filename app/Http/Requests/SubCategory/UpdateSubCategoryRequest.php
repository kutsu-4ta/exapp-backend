<?php

namespace App\Http\Requests\SubCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', Rule::exists('subjects', 'name')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
