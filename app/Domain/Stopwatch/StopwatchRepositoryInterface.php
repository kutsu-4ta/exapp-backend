<?php

namespace App\Domain\Stopwatch;

use App\Models\Stopwatch;

interface StopwatchRepositoryInterface
{
    /**
     * ユーザーとセッションキーに対応するストップウォッチを取得する。
     * 存在しない場合は初期状態で新規作成して返す。
     */
    public function findOrCreate(int $userId, string $sessionKey = 'default'): Stopwatch;

    public function save(Stopwatch $stopwatch): Stopwatch;
}
