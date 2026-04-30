<?php

namespace App\Http\Requests\SubCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSubCategoriesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', Rule::exists('subjects', 'name')],
        ];
    }
}
