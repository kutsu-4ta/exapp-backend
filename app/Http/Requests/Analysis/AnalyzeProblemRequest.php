<?php

namespace App\Http\Requests\Analysis;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeProblemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,heic',
                'max:10240', // 10MB
            ],
        ];
    }
}
