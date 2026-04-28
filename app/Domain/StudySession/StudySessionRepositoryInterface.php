<?php

namespace App\Domain\StudySession;

use App\Models\StudySession;

interface StudySessionRepositoryInterface
{
    public function create(int $dailyLogId, array $data): StudySession;

    public function findByIdAndUser(int $id, int $userId): ?StudySession;

    public function update(StudySession $session, array $data): StudySession;

    public function delete(StudySession $session): void;
}
