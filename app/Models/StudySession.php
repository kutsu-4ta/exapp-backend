<?php

namespace App\Models;

use App\Enums\TimeSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $fillable = [
        'daily_log_id',
        'subject_id',
        'sub_category_id',
        'time_slot',
        'minutes',
        'material_id',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'sub_category_id' => 'integer',
            'time_slot' => TimeSlot::class,
            'material_id' => 'integer',
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

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function material()
    {
        return $this->belongsTo( Material::class);
    }
}
