<?php

namespace App\Http\Requests\StudyTicket;

use Illuminate\Foundation\Http\FormRequest;

class MoveTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sprintId' => ['required', 'integer', 'exists:sprints,id'],
        ];
    }
}
