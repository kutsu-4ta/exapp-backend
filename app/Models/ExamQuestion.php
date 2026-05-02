<?php

namespace App\Models;

use App\Enums\Rank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_session_id',
        'sort_order',
        'display_id',
        'is_sub',
        'has_children',
        'rank',
        'my_answer',
        'is_correct',
        'is_doubtful',
        'point',
        'note',
        'answered_time_ms',
        'answered_started_at',
        'answered_finished_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order'          => 'integer',
            'is_sub'              => 'boolean',
            'has_children'        => 'boolean',
            'rank'                => Rank::class,
            'is_correct'          => 'boolean',
            'is_doubtful'         => 'boolean',
            'point'               => 'integer',
            'answered_time_ms'    => 'integer',
            'answered_started_at' => 'datetime',
            'answered_finished_at' => 'datetime',
        ];
    }

    /**
     * answered_started_at と answered_finished_at の差分をミリ秒で返す。
     * どちらかが null または finished < started の場合は null。
     */
    public static function computeAnsweredTimeMs(
        ?\Illuminate\Support\Carbon $startedAt,
        ?\Illuminate\Support\Carbon $finishedAt,
    ): ?int {
        if ($startedAt === null || $finishedAt === null) {
            return null;
        }

        $ms = (int) $startedAt->diffInMilliseconds($finishedAt);

        return $ms >= 0 ? $ms : null;
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}
