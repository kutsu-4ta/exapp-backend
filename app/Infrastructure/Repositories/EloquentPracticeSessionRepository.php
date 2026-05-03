<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Practice\PracticeSessionRepositoryInterface;
use App\Enums\TimeSlot;
use App\Models\DailyLog;
use App\Models\PracticeSession;
use App\Models\StudySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentPracticeSessionRepository implements PracticeSessionRepositoryInterface
{
    public function create(
        int    $userId,
        int    $subjectId,
        string $date,
        int    $totalElapsedMs,
        int    $totalMinutes,
    ): PracticeSession {
        return DB::transaction(function () use ($userId, $subjectId, $date, $totalElapsedMs, $totalMinutes) {
            $session = PracticeSession::create([
                'user_id'          => $userId,
                'subject_id'       => $subjectId,
                'date'             => $date,
                'total_elapsed_ms' => $totalElapsedMs,
            ]);

            if ($totalMinutes > 0) {
                $dailyLog = DailyLog::firstOrCreate(
                    ['user_id' => $userId, 'date' => $date],
                );

                StudySession::create([
                    'daily_log_id' => $dailyLog->id,
                    'subject_id'   => $subjectId,
                    'time_slot'    => $this->resolveTimeSlot()->value,
                    'minutes'      => $totalMinutes,
                ]);
            }

            return $session->load('subject');
        });
    }

    private function resolveTimeSlot(): TimeSlot
    {
        $hour = (int) Carbon::now()->format('H');

        return match (true) {
            $hour >= 5  && $hour < 10 => TimeSlot::Morning,
            $hour >= 10 && $hour < 14 => TimeSlot::Lunch,
            $hour >= 14 && $hour < 19 => TimeSlot::Commute,
            default                   => TimeSlot::Night,
        };
    }
}
