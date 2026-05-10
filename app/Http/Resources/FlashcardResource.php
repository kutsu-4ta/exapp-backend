<?php

namespace App\Http\Resources;

use App\Enums\FailureType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'front' => [
                'questionRef' => $this->question_ref,
                'subCategory' => $this->subCategory?->name,
                'material'    => $this->material?->name,
                'solvedAt'    => $this->solved_at->toDateString(),
            ],
            'back' => [
                'note'          => $this->note,
                'proficiency'   => $this->proficiency->value,
                'failureTypes'  => array_map(
                    fn (string $v) => FailureType::from($v)->value,
                    $this->failure_types ?? []
                ),
                'isGoodQuestion' => $this->is_good_question,
            ],
        ];
    }
}
