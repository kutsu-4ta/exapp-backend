<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date->toDateString(),
            'reflection' => $this->reflection,
            'isCompleted' => $this->is_completed,
            'completedAt' => $this->completed_at?->toIso8601String(),
            'studySessions' => StudySessionResource::collection($this->studySessions),
            'totalMinutes' => $this->studySessions->sum('minutes'),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
