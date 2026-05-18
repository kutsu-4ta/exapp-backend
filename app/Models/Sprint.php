<?php

namespace App\Models;

use App\Enums\SprintStatus;
use App\Enums\SprintType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'goal',
        'type',
        'status',
        'start_date',
        'end_date',
        'completed_at',
        'retrospective',
    ];

    protected function casts(): array
    {
        return [
            'type'         => SprintType::class,
            'status'       => SprintStatus::class,
            'start_date'   => 'date',
            'end_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(StudyTicket::class);
    }

    public function isBacklog(): bool
    {
        return $this->type === SprintType::Backlog;
    }

    public function completionRate(): float
    {
        $total = $this->tickets()->count();
        if ($total === 0) {
            return 0.0;
        }
        $done = $this->tickets()->where('status', 'done')->count();

        return round($done / $total * 100, 1);
    }
}
