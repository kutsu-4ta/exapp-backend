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
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'status' => ExamSessionStatus::class,
            'completed_at' => 'datetime',
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
        return (int) $this->questions->whereNotNull('is_correct')->where('is_correct', true)->sum('point');
    }

    public function pureScore(): int
    {
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
