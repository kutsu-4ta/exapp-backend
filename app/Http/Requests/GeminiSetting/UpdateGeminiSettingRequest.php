<?php

namespace App\Http\Requests\GeminiSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeminiSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'geminiModel' => ['required', 'string', 'max:100'],
        ];
    }
}
