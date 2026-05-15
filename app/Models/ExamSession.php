<?php

namespace App\Models;

use App\Enums\ExamSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'exam_year',
        'status',
        'completed_at',
        'total_score',
        'pure_score',
    ];

    protected function casts(): array
    {
        return [
            'subject_id'  => 'integer',
            'status'      => ExamSessionStatus::class,
            'completed_at' => 'datetime',
            'total_score' => 'integer',
            'pure_score'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order');
    }

    public function totalScore(): int
    {
        // quick-score セッションは stored value を使用
        if ($this->total_score !== null) {
            return $this->total_score;
        }
        return (int) $this->questions->whereNotNull('is_correct')->where('is_correct', true)->sum('point');
    }

    public function pureScore(): int
    {
        if ($this->pure_score !== null) {
            return $this->pure_score;
        }
        return (int) $this->questions
            ->where('is_correct', true)
            ->where('is_doubtful', false)
            ->sum('point');
    }

    public function correctCount(): int
    {
        return $this->questions->where('is_correct', true)->count();
    }

    public function incorrectCount(): int
    {
        return $this->questions->where('is_correct', false)->count();
    }

    public function doubtfulCount(): int
    {
        return $this->questions->where('is_doubtful', true)->count();
    }
}
