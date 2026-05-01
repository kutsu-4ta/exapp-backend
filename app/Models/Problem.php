<?php

namespace App\Models;

use App\Enums\Proficiency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Problem extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'sub_category_id',
        'material_id',
        'question_ref',
        'note',
        'proficiency',
        'failure_types',
        'is_good_question',
        'solved_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'sub_category_id' => 'integer',
            'material_id' => 'integer',
            'proficiency' => Proficiency::class,
            'failure_types' => 'array',
            'is_good_question' => 'boolean',
            'solved_at' => 'date',
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

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
