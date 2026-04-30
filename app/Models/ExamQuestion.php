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
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_sub' => 'boolean',
            'has_children' => 'boolean',
            'rank' => Rank::class,
            'is_correct' => 'boolean',
            'is_doubtful' => 'boolean',
            'point' => 'integer',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}
