<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'display_order',
    ];

    /**
     * この教材を使用した学習セッション一覧
     */
    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
