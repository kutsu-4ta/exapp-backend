<?php

namespace App\Http\Requests\StudyTicket;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'            => ['sometimes', 'string', 'max:255'],
            'title'              => ['sometimes', 'string', 'max:255'],
            'acceptanceCriteria' => ['sometimes', 'string'],
            'dueDate'            => ['sometimes', 'date'],
            'status'             => ['sometimes', new Enum(TicketStatus::class)],
            'priority'           => ['sometimes', new Enum(TicketPriority::class)],
            'ticketType'         => ['sometimes', new Enum(TicketType::class)],
            'source'             => ['sometimes', new Enum(TicketSource::class)],
            'estimateMinutes'    => ['nullable', 'integer', 'min:1', 'max:480'],
            'subCategoryIds'     => ['sometimes', 'array', 'min:1'],
            'subCategoryIds.*'   => ['integer', 'exists:sub_categories,id'],
        ];
    }
}
