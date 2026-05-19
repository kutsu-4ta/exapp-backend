<?php

namespace App\Infrastructure\Repositories;

use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Enums\ExamSessionStatus;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Subject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentExamSessionRepository implements ExamSessionRepositoryInterface
{
    public function findAllByUser(int $userId, ?string $status = null, ?string $subject = null): Collection
    {
        $query = ExamSession::with(['subject', 'questions'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($subject !== null) {
            $query->whereHas('subject', fn ($q) => $q->where('name', $subject)->where('user_id', $userId));
        }

        return $query->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?ExamSession
    {
        return ExamSession::with(['subject', 'questions'])
            ->where('user_id', $userId)
            ->find($id);
    }

    public function create(int $userId, array $data): ExamSession
    {
        $session = ExamSession::create([
            'user_id'    => $userId,
            'subject_id' => $data['subject_id'],
            'exam_year'  => $data['exam_year'],
            'status'     => ExamSessionStatus::InProgress,
        ]);

        return $session->load(['subject', 'questions']);
    }

    public function createCompleted(int $userId, array $data): ExamSession
    {
        $session = ExamSession::create([
            'user_id'      => $userId,
            'subject_id'   => $data['subject_id'],
            'exam_year'    => $data['exam_year'],
            'status'       => ExamSessionStatus::Completed,
            'total_score'  => $data['total_score'],
            'pure_score'   => $data['pure_score'],
            'completed_at' => Carbon::now(),
        ]);

        return $session->load(['subject', 'questions']);
    }

    public function update(ExamSession $session, array $data): ExamSession
    {
        $session->update($data);

        return $session->fresh(['subject', 'questions']);
    }

    public function delete(ExamSession $session): void
    {
        $session->delete();
    }

    public function complete(ExamSession $session, array $questions): ExamSession
    {
        DB::transaction(function () use ($session, $questions) {
            $session->questions()->delete();

            $rows = array_map(fn (array $q) => array_merge($q, [
                'exam_session_id' => $session->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]), $questions);

            ExamQuestion::insert($rows);

            $updateData = ['status' => ExamSessionStatus::Completed];

            if ($session->completed_at === null) {
                $updateData['completed_at'] = Carbon::now();
            }

            $session->update($updateData);
        });

        return $session->fresh(['subject', 'questions']);
    }

    public function upsertQuestion(int $sessionId, int $sortOrder, array $data): ExamQuestion
    {
        $question = ExamQuestion::firstOrCreate(
            ['exam_session_id' => $sessionId, 'sort_order' => $sortOrder],
            [
                'display_id'   => $data['display_id']   ?? (string) $sortOrder,
                'is_sub'       => $data['is_sub']        ?? false,
                'has_children' => $data['has_children']  ?? false,
                'rank'         => $data['rank']           ?? 'C',
                'is_doubtful'  => $data['is_doubtful']   ?? false,
                'point'        => $data['point']          ?? 0,
            ],
        );

        $changed = false;

        // answered_started_at は初回のみセット（上書きしない）
        if (isset($data['answered_started_at']) && $question->answered_started_at === null) {
            $question->answered_started_at = $data['answered_started_at'];
            $changed = true;
        }

        if (isset($data['answered_finished_at'])) {
            $question->answered_finished_at = $data['answered_finished_at'];
            $question->answered_time_ms     = ExamQuestion::computeAnsweredTimeMs(
                $question->answered_started_at,
                $question->answered_finished_at,
            );
            $changed = true;
        }

        if ($changed) {
            $question->save();
        }

        return $question->fresh();
    }

    public function findCompletedBySubject(int $userId, int $subjectId, ?int $year = null, ?int $month = null): Collection
    {
        $query = ExamSession::with('questions')
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('status', ExamSessionStatus::Completed)
            ->orderByDesc('completed_at');

        if ($year !== null) {
            $query->whereYear('completed_at', $year);
        }
        if ($month !== null) {
            $query->whereMonth('completed_at', $month);
        }

        return $query->get();
    }
}
