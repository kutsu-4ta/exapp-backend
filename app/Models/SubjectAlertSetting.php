<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectAlertSetting extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'touch_alert_enabled',
        'threshold_days',
        'include_untouched',
        'minutes_alert_enabled',
        'minutes_threshold_days',
        'minutes_threshold',
    ];

    protected function casts(): array
    {
        return [
            'touch_alert_enabled'   => 'boolean',
            'threshold_days'        => 'integer',
            'include_untouched'     => 'boolean',
            'minutes_alert_enabled' => 'boolean',
            'minutes_threshold_days' => 'integer',
            'minutes_threshold'     => 'integer',
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
