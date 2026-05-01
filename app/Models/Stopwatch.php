<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stopwatch extends Model
{
    protected $fillable = [
        'user_id',
        'session_key',
        'is_running',
        'started_at',
        'elapsed_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_running'       => 'boolean',
            'started_at'       => 'datetime',
            'elapsed_seconds'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
