<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Practice\PracticeSessionDraftRepositoryInterface;
use App\Models\PracticeSessionDraft;

class EloquentPracticeSessionDraftRepository implements PracticeSessionDraftRepositoryInterface
{
    public function upsert(int $userId, int $subjectId, int $currentIndex, array $log): PracticeSessionDraft
    {
        return PracticeSessionDraft::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            ['current_index' => $currentIndex, 'log' => $log],
        );
    }

    public function findBySubject(int $userId, int $subjectId): ?PracticeSessionDraft
    {
        return PracticeSessionDraft::with('subject')
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    public function deleteBySubject(int $userId, int $subjectId): void
    {
        PracticeSessionDraft::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->delete();
    }
}
