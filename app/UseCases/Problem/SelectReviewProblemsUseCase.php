<?php

namespace App\UseCases\Problem;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Enums\FailureType;
use App\Models\Problem;
use Illuminate\Support\Collection;

class SelectReviewProblemsUseCase
{
    private const LIMIT = 5;

    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, string $subjectName): Collection
    {
        $subject = $this->subjectRepository->findByName($userId, $subjectName);

        if ($subject === null) {
            return collect();
        }

        // 優先順位:
        // 1. last_touched_at が古い順（NULL = 一度も演習していない → 最優先）
        // 2. 定義を含むものを優先
        return Problem::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->orderByRaw('last_touched_at ASC NULLS FIRST')
            ->orderByRaw("CASE WHEN failure_types @> ?::jsonb THEN 0 ELSE 1 END", [
                json_encode([FailureType::MissingDefinition->value]),
            ])
            ->limit(self::LIMIT)
            ->get();
    }
}
