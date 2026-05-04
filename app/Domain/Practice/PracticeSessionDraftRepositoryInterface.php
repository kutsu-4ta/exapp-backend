<?php

namespace App\Domain\Practice;

use App\Models\PracticeSessionDraft;

interface PracticeSessionDraftRepositoryInterface
{
    /** 同一 user×subject のドラフトが存在すれば上書き、なければ新規作成する。 */
    public function upsert(int $userId, int $subjectId, int $currentIndex, array $log): PracticeSessionDraft;

    public function findBySubject(int $userId, int $subjectId): ?PracticeSessionDraft;

    public function deleteBySubject(int $userId, int $subjectId): void;
}
