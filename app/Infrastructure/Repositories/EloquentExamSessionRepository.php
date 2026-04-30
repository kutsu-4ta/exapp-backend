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
            $query->whereHas('subject', fn ($q) => $q->where('name', $subject));
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
            'user_id' => $userId,
            'subject_id' => $data['subject_id'],
            'exam_year' => $data['exam_year'],
            'status' => ExamSessionStatus::InProgress,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]), $questions);

            ExamQuestion::insert($rows);

            $session->update([
                'status' => ExamSessionStatus::Completed,
                'completed_at' => Carbon::now(),
            ]);
        });

        return $session->fresh(['subject', 'questions']);
    }

    public function findCompletedBySubject(int $userId, int $subjectId): Collection
    {
        return ExamSession::with('questions')
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('status', ExamSessionStatus::Completed)
            ->orderByDesc('completed_at')
            ->get();
    }
}
