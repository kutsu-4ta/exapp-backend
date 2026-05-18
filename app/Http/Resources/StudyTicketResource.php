<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'sprintId'           => $this->sprint_id,
            'subject'            => $this->subject?->name,
            'title'              => $this->title,
            'acceptanceCriteria' => $this->acceptance_criteria,
            'dueDate'            => $this->due_date->toDateString(),
            'status'             => $this->status->value,
            'priority'           => $this->priority->value,
            'ticketType'         => $this->ticket_type->value,
            'source'             => $this->source->value,
            'estimateMinutes'    => $this->estimate_minutes,
            'subCategories'      => $this->subCategories->map(fn ($sc) => [
                'id'      => $sc->id,
                'name'    => $sc->name,
                'subject' => $sc->subject?->name,
            ]),
            'completedAt'        => $this->completed_at?->toIso8601String(),
            'createdAt'          => $this->created_at->toIso8601String(),
        ];
    }
}
