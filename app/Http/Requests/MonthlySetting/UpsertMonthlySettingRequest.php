<?php

namespace App\Http\Requests\MonthlySetting;

use Illuminate\Foundation\Http\FormRequest;

class UpsertMonthlySettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'targetMin' => ['required', 'numeric', 'min:0'],
            'targetMax' => ['required', 'numeric', 'min:0', 'gte:targetMin'],
        ];
    }
}
