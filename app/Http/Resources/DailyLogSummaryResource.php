<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyLogSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date'         => $this->date->toDateString(),
            'isCompleted'  => $this->is_completed,
            'totalMinutes' => (int) ($this->study_sessions_sum_minutes ?? 0),
            'sessionCount' => (int) ($this->study_sessions_count ?? 0),
            'slotMinutes'  => [
                'morning' => (int) ($this->morning_minutes ?? 0),
                'lunch'   => (int) ($this->lunch_minutes   ?? 0),
                'night'   => (int) ($this->night_minutes   ?? 0),
            ],
        ];
    }
}
