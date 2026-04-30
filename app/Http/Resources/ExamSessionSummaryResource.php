<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSessionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject->name,
            'examYear' => $this->exam_year,
            'totalScore' => $this->totalScore(),
            'pureScore' => $this->pureScore(),
            'createdAt' => $this->created_at->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
        ];
    }
}
