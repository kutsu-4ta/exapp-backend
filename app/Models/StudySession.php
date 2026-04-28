<?php

namespace App\Models;

use App\Enums\Material;
use App\Enums\TimeSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $fillable = [
        'daily_log_id',
        'subject_id',
        'time_slot',
        'minutes',
        'material',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'time_slot' => TimeSlot::class,
            'material' => Material::class,
            'minutes' => 'integer',
        ];
    }

    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
