<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'occupation_en',
        'goal_en',
        'interests_en',
        'strong_areas_en',
        'weak_subjects_json',
        'study_style_json',
        'normalized_prompt_json',
        'translation_version',
        'gemini_model',
    ];

    protected function casts(): array
    {
        return [
            'weak_subjects_json'     => 'array',
            'study_style_json'       => 'array',
            'normalized_prompt_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
