<?php

namespace App\UseCases\Subject;

use App\Models\Problem;
use App\Models\StudySession;
use App\Models\Subject;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetSubjectActivityUseCase
{
    public function __invoke(int $userId, string $subjectName, int $year, int $month): array
    {
        $subject = Subject::where('name', $subjectName)->first();

        if ($subject === null) {
            throw new ModelNotFoundException("Subject '{$subjectName}' not found.");
        }

        $studyByDate = StudySession::join('daily_logs', 'daily_logs.id', '=', 'study_sessions.daily_log_id')
            ->where('daily_logs.user_id', $userId)
            ->where('study_sessions.subject_id', $subject->id)
            ->whereYear('daily_logs.date', $year)
            ->whereMonth('daily_logs.date', $month)
            ->groupBy('daily_logs.date')
            ->get([
                'daily_logs.date as date',
                DB::raw('SUM(study_sessions.minutes) as minutes'),
            ])
            ->keyBy(fn ($r) => $r->date instanceof Carbon ? $r->date->toDateString() : (string) $r->date);

        $problemByDate = Problem::where('user_id', $userId)
            ->where('subject_id', $subject->id)
            ->whereYear('solved_at', $year)
            ->whereMonth('solved_at', $month)
            ->groupBy('solved_at')
            ->get([
                'solved_at',
                DB::raw('COUNT(*) as cnt'),
            ])
            ->keyBy(fn ($r) => $r->solved_at instanceof Carbon ? $r->solved_at->toDateString() : (string) $r->solved_at);

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $result = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = Carbon::create($year, $month, $day)->toDateString();
            $result[] = [
                'date'         => $dateStr,
                'studyMinutes' => isset($studyByDate[$dateStr]) ? (int) $studyByDate[$dateStr]->minutes : 0,
                'problemCount' => isset($problemByDate[$dateStr]) ? (int) $problemByDate[$dateStr]->cnt : 0,
            ];
        }

        return $result;
    }
}
