<?php

namespace App\Domain\ExamSession;

use App\Models\ExamQuestion;
use App\Models\ExamSession;
use Illuminate\Support\Collection;

interface ExamSessionRepositoryInterface
{
    public function findAllByUser(int $userId, ?string $status = null, ?string $subject = null): Collection;

    public function findByIdAndUser(int $id, int $userId): ?ExamSession;

    public function create(int $userId, array $data): ExamSession;

    public function createCompleted(int $userId, array $data): ExamSession;

    public function update(ExamSession $session, array $data): ExamSession;

    public function delete(ExamSession $session): void;

    public function complete(ExamSession $session, array $questions): ExamSession;

    /**
     * 問題1件を upsert する（中間タイムスタンプ更新用）。
     * sort_order が一致する既存レコードがあれば更新、なければ作成する。
     */
    public function upsertQuestion(int $sessionId, int $sortOrder, array $data): ExamQuestion;
}
