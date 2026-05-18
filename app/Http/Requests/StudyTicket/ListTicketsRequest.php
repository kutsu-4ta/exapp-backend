<?php

namespace App\Http\Requests\StudyTicket;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListTicketsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sprintId' => ['nullable', 'integer', 'exists:sprints,id'],
            'status'   => ['nullable', new Enum(TicketStatus::class)],
        ];
    }
}
