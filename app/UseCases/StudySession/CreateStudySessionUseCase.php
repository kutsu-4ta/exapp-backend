<?php

namespace App\UseCases\StudySession;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\StudySession;

class CreateStudySessionUseCase
{
    public function __construct(
        private readonly StudySessionRepositoryInterface $sessionRepository,
        private readonly DailyLogRepositoryInterface $dailyLogRepository,
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly MaterialRepositoryInterface $materialRepository,
        private readonly SubCategoryRepositoryInterface $subCategoryRepository,
    ) {}

    public function __invoke(int $userId, string $dailyLogDate, array $data): StudySession
    {
        $dailyLog = $this->dailyLogRepository->findByDate($userId, $dailyLogDate);

        if ($dailyLog === null) {
            abort(422, '指定日のデイリーログが存在しません。先にデイリーログを作成してください。');
        }

        $subjectId  = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;
        $materialId = $this->materialRepository->firstOrCreate($userId, $data['material'])->id;

        $subCategoryId = null;
        if (!empty($data['sub_category'])) {
            $subCategoryId = $this->subCategoryRepository->firstOrCreate($userId, $subjectId, $data['sub_category'])->id;
        }

        return $this->sessionRepository->create($dailyLog->id, array_merge(
            array_diff_key($data, ['subject' => null, 'material' => null, 'sub_category' => null]),
            [
                'subject_id'      => $subjectId,
                'material_id'     => $materialId,
                'sub_category_id' => $subCategoryId,
            ]
        ));
    }
}
