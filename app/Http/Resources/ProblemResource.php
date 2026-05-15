<?php

namespace App\Http\Resources;

use App\Enums\FailureType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject'     => $this->subject->name,
            'materialName'    => $this->material?->name,
            'subCategory' => $this->subCategory?->name,
            'questionRef' => $this->question_ref,
            'note' => $this->note,
            'proficiency' => $this->proficiency->value,
            'failureTypes' => array_map(
                fn (string $v) => FailureType::from($v)->value,
                $this->failure_types ?? []
            ),
            'isGoodQuestion' => $this->is_good_question,
            'isFormula'      => $this->is_formula,
            'solvedAt' => $this->solved_at->toDateString(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
