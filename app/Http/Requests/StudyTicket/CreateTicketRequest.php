<?php

namespace App\Http\Requests\StudyTicket;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sprintId'            => ['nullable', 'integer', 'exists:sprints,id'],
            'subject'             => ['required', 'string', 'max:255'],
            'title'               => ['required', 'string', 'max:255'],
            'acceptanceCriteria'  => ['required', 'string'],
            'dueDate'             => ['required', 'date'],
            'priority'            => ['required', new Enum(TicketPriority::class)],
            'ticketType'          => ['required', new Enum(TicketType::class)],
            'source'              => ['required', new Enum(TicketSource::class)],
            'estimateMinutes'     => ['nullable', 'integer', 'min:1', 'max:480'],
            'subCategoryIds'      => ['required', 'array', 'min:1'],
            'subCategoryIds.*'    => ['integer', 'exists:sub_categories,id'],
        ];
    }
}
