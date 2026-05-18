<?php

namespace App\Http\Requests\TicketNote;

use Illuminate\Foundation\Http\FormRequest;

class TicketNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
