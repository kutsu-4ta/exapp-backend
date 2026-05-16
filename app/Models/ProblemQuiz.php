<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemQuiz extends Model
{
    protected $fillable = [
        'problem_id',
        'quiz_type',
        'question',
        'options',
        'correct_index',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'problem_id'    => 'integer',
            'options'       => 'array',
            'correct_index' => 'integer',
        ];
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }
}
