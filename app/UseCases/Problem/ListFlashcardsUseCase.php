<?php

namespace App\UseCases\Problem;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Problem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListFlashcardsUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, string $subjectName, ?int $limit = null, ?int $afterId = null): Collection
    {
        $subject = $this->subjectRepository->findByName($userId, $subjectName);

        if ($subject === null) {
            return collect();
        }

        $query = Problem::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->orderByDesc('solved_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($afterId !== null) {
            $cursor = Problem::where('user_id', $userId)->find($afterId);
            if ($cursor) {
                $solvedAt  = $cursor->solved_at;
                $createdAt = $cursor->created_at;
                $cursorId  = $cursor->id;
                $query->where(function ($q) use ($solvedAt, $createdAt, $cursorId) {
                    $q->where('solved_at', '<', $solvedAt)
                      ->orWhere(function ($q2) use ($solvedAt, $createdAt, $cursorId) {
                          $q2->where('solved_at', $solvedAt)
                             ->where(function ($q3) use ($createdAt, $cursorId) {
                                 $q3->where('created_at', '<', $createdAt)
                                    ->orWhere(function ($q4) use ($createdAt, $cursorId) {
                                        $q4->where('created_at', $createdAt)
                                           ->where('id', '<', $cursorId);
                                    });
                             });
                      });
                });
            }
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
