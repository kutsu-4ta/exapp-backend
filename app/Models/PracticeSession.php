<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeSession extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'date',
        'total_elapsed_ms',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'total_elapsed_ms' => 'integer',
        ];
    }

    /** totalElapsedMs → 分（切り上げ、最低 1）。0ms の場合は 0 を返す。 */
    public function getTotalMinutesAttribute(): int
    {
        if ($this->total_elapsed_ms <= 0) {
            return 0;
        }

        return max(1, (int) ceil($this->total_elapsed_ms / 60000));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
