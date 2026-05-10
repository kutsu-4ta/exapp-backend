<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\FlashcardSelectionStrategyInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;

class ListAllFlashcardsUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly FlashcardSelectionStrategyInterface $strategy,
    ) {}

    public function __invoke(int $userId, ?string $subjectName = null, int $count = 10): Collection
    {
        $query = Problem::where('user_id', $userId);

        if ($subjectName !== null) {
            $subject = $this->subjectRepository->findByName($userId, $subjectName);
            if ($subject === null) {
                return collect();
            }
            $query->where('subject_id', $subject->id);
        }

        return $this->strategy->select($query, $count);
    }
}
