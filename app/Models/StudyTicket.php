<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyTicket extends Model
{
    protected $fillable = [
        'user_id',
        'sprint_id',
        'subject_id',
        'title',
        'acceptance_criteria',
        'due_date',
        'status',
        'priority',
        'ticket_type',
        'source',
        'estimate_minutes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'           => TicketStatus::class,
            'priority'         => TicketPriority::class,
            'ticket_type'      => TicketType::class,
            'source'           => TicketSource::class,
            'due_date'         => 'date',
            'completed_at'     => 'datetime',
            'estimate_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subCategories(): BelongsToMany
    {
        return $this->belongsToMany(SubCategory::class, 'ticket_sub_categories', 'ticket_id', 'sub_category_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class, 'ticket_id')->orderBy('created_at');
    }
}
