<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject->name,
            'examYear' => $this->exam_year,
            'status' => $this->status->value,
            'questions' => ExamQuestionResource::collection($this->questions),
            'totalScore' => $this->totalScore(),
            'pureScore' => $this->pureScore(),
            'correctCount' => $this->correctCount(),
            'incorrectCount' => $this->incorrectCount(),
            'doubtfulCount' => $this->doubtfulCount(),
            'createdAt' => $this->created_at->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
        ];
    }
}
