<?php

namespace App\UseCases\StudySession;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Models\StudySession;

class CreateStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $sessionRepository,
        private readonly DailyLogRepositoryInterface $dailyLogRepository,
    ) {}

    public function __invoke(int $userId, string $dailyLogDate, array $data): StudySession
    {
        $dailyLog = $this->dailyLogRepository->findByDate($userId, $dailyLogDate);

        if ($dailyLog === null) {
            abort(422, '指定日のデイリーログが存在しません。先にデイリーログを作成してください。');
        }

        return $this->sessionRepository->create($dailyLog->id, $data);
    }
}
