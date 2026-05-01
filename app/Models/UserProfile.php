<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nickname',
        'occupation',
        'goal',
        'weak_areas',
        'strong_areas',
        'interests',
        'gemini_token',
    ];

    /**
     * gemini_token はDBに暗号化して保存し、アクセス時に自動復号する。
     * シリアライズ（JSON出力）には含めない。
     */
    protected $hidden = ['gemini_token'];

    protected function casts(): array
    {
        return [
            'gemini_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
