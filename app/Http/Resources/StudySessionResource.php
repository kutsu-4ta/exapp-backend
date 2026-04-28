<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dailyLogDate' => $this->dailyLog->date->toDateString(),
            'subjectId' => $this->subject_id,
            'subject' => $this->subject->name,
            'timeSlot' => $this->time_slot->value,
            'minutes' => $this->minutes,
            'material' => $this->material->value,
            'memo' => $this->memo,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
