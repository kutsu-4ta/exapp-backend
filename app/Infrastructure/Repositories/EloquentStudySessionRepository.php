<?php

namespace App\Infrastructure\Repositories;

use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Models\StudySession;

class EloquentStudySessionRepository implements StudySessionRepositoryInterface
{
    public function create(int $dailyLogId, array $data): StudySession
    {
        $session = StudySession::create(array_merge(['daily_log_id' => $dailyLogId], $data));

        return $session->load(['dailyLog', 'subject']);
    }

    public function findByIdAndUser(int $id, int $userId): ?StudySession
    {
        return StudySession::with(['dailyLog', 'subject'])
            ->whereHas('dailyLog', fn ($q) => $q->where('user_id', $userId))
            ->find($id);
    }

    public function update(StudySession $session, array $data): StudySession
    {
        $session->update($data);

        return $session->load(['dailyLog', 'subject']);
    }

    public function delete(StudySession $session): void
    {
        $session->delete();
    }
}
