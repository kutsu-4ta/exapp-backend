<?php

namespace App\Models;

use App\Enums\Rank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCategory extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'name',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'rank'       => Rank::class,
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
