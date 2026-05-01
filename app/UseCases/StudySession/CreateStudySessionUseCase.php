<?php

namespace App\UseCases\StudySession;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\StudySession;

class CreateStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $sessionRepository,
        private readonly DailyLogRepositoryInterface $dailyLogRepository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, string $dailyLogDate, array $data): StudySession
    {
        $dailyLog = $this->dailyLogRepository->findByDate($userId, $dailyLogDate);

        if ($dailyLog === null) {
            abort(422, '指定日のデイリーログが存在しません。先にデイリーログを作成してください。');
        }

        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->sessionRepository->create($dailyLog->id, array_merge(
            array_diff_key($data, ['subject' => null]),
            ['subject_id' => $subjectId],
        ));
    }
}
