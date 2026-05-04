<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeSessionDraft extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'current_index',
        'log',
    ];

    protected function casts(): array
    {
        return [
            'current_index' => 'integer',
            'log'           => 'array',
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
}
