<?php

namespace App\Http\Requests\Snippet;

use Illuminate\Foundation\Http\FormRequest;

class CreateSnippetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:60'],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }
}
