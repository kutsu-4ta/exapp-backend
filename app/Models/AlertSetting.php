<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertSetting extends Model
{
    protected $fillable = [
        'user_id',
        'threshold_days',
        'include_untouched',
    ];

    protected function casts(): array
    {
        return [
            'threshold_days'    => 'integer',
            'include_untouched' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
