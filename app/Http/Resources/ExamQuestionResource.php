<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'examSessionId' => $this->exam_session_id,
            'sortOrder' => $this->sort_order,
            'displayId' => $this->display_id,
            'isSub' => $this->is_sub,
            'hasChildren' => $this->has_children,
            'rank' => $this->rank?->value,
            'myAnswer' => $this->my_answer,
            'isCorrect' => $this->is_correct,
            'isDoubtful' => $this->is_doubtful,
            'point' => $this->point,
            'note'               => $this->note,
            'answeredTimeMs'     => $this->answered_time_ms,
            'answeredStartedAt'  => $this->answered_started_at?->toIso8601String(),
            'answeredFinishedAt' => $this->answered_finished_at?->toIso8601String(),
        ];
    }
}
