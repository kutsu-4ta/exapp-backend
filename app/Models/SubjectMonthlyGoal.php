<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectMonthlyGoal extends Model
{
    protected $fillable = ['user_id', 'subject_id', 'year', 'month', 'goal'];

    protected function casts(): array
    {
        return [
            'year'  => 'integer',
            'month' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
