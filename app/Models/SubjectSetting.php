<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectSetting extends Model
{
    protected $fillable = ['user_id', 'subject_id', 'final_target'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
