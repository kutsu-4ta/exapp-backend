<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'goal'        => $this->goal,
            'type'        => $this->type->value,
            'status'      => $this->status->value,
            'startDate'   => $this->start_date?->toDateString(),
            'endDate'     => $this->end_date?->toDateString(),
            'completedAt'   => $this->completed_at?->toIso8601String(),
            'retrospective' => $this->retrospective,
            'createdAt'     => $this->created_at->toIso8601String(),
        ];
    }
}
