<?php

namespace App\UseCases\ExamSession;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Models\ExamQuestion;
use Illuminate\Support\Carbon;

class UpdateExamQuestionTimestampUseCase
{
    public function __construct(
        private readonly ExamSessionRepositoryInterface $repository,
    ) {}

    /**
     * @param array{
     *   event: 'start'|'finish',
     *   displayId?: string,
     *   isSub?: bool,
     *   hasChildren?: bool,
     *   rank?: string,
     *   isDoubtful?: bool,
     *   point?: int,
     * } $data
     */
    public function __invoke(int $sessionId, int $userId, int $sortOrder, array $data): ExamQuestion
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            abort(404);
        }

        $now = Carbon::now();

        $questionData = [
            'display_id'   => $data['displayId']   ?? null,
            'is_sub'       => $data['isSub']        ?? null,
            'has_children' => $data['hasChildren']  ?? null,
            'rank'         => $data['rank']          ?? null,
            'is_doubtful'  => $data['isDoubtful']   ?? null,
            'point'        => $data['point']         ?? null,
        ];

        // null を除いて渡す（firstOrCreate のデフォルト値は repository 側が持つ）
        $questionData = array_filter($questionData, fn ($v) => $v !== null);

        if ($data['event'] === 'start') {
            $questionData['answered_started_at'] = $now;
        } else {
            // finish: サーバー時刻で確定
            $questionData['answered_finished_at'] = $now;
        }

        return $this->repository->upsertQuestion($sessionId, $sortOrder, $questionData);
    }
}
