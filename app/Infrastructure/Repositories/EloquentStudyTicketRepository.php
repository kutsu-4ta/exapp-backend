<?php

namespace App\Infrastructure\Repositories;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Enums\TicketStatus;
use App\Models\StudyTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentStudyTicketRepository implements StudyTicketRepositoryInterface
{
    public function findAllByUser(int $userId, ?int $sprintId = null, ?string $status = null): Collection
    {
        $query = StudyTicket::with(['subject', 'subCategories.subject', 'sprint'])
            ->where('user_id', $userId)
            ->orderBy('due_date')
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END");

        if ($sprintId !== null) {
            $query->where('sprint_id', $sprintId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?StudyTicket
    {
        return StudyTicket::with(['subject', 'subCategories.subject', 'sprint'])
            ->where('user_id', $userId)
            ->find($id);
    }

    public function create(int $userId, array $data, array $subCategoryIds): StudyTicket
    {
        $ticket = StudyTicket::create([
            'user_id'              => $userId,
            'sprint_id'            => $data['sprint_id'],
            'subject_id'           => $data['subject_id'] ?? null,
            'title'                => $data['title'],
            'acceptance_criteria'  => $data['acceptance_criteria'],
            'due_date'             => $data['due_date'],
            'status'               => TicketStatus::Todo->value,
            'priority'             => $data['priority'],
            'ticket_type'          => $data['ticket_type'],
            'source'               => $data['source'],
            'estimate_minutes'     => $data['estimate_minutes'] ?? null,
        ]);

        $ticket->subCategories()->sync($subCategoryIds);

        return $ticket->load(['subject', 'subCategories.subject', 'sprint']);
    }

    public function update(StudyTicket $ticket, array $data, ?array $subCategoryIds = null): StudyTicket
    {
        $updateData = array_filter([
            'sprint_id'           => $data['sprint_id'] ?? null,
            'subject_id'          => $data['subject_id'] ?? null,
            'title'               => $data['title'] ?? null,
            'acceptance_criteria' => $data['acceptance_criteria'] ?? null,
            'due_date'            => $data['due_date'] ?? null,
            'status'              => $data['status'] ?? null,
            'priority'            => $data['priority'] ?? null,
            'ticket_type'         => $data['ticket_type'] ?? null,
            'source'              => $data['source'] ?? null,
            'estimate_minutes'    => $data['estimate_minutes'] ?? null,
        ], fn ($v) => $v !== null);

        // status が done → todo/doing に戻る場合は completed_at をクリア
        if (isset($updateData['status']) && $updateData['status'] !== TicketStatus::Done->value) {
            $updateData['completed_at'] = null;
        }

        // status が done になる場合は completed_at をセット
        if (isset($updateData['status']) && $updateData['status'] === TicketStatus::Done->value) {
            $updateData['completed_at'] = Carbon::now();
        }

        $ticket->update($updateData);

        if ($subCategoryIds !== null) {
            $ticket->subCategories()->sync($subCategoryIds);
        }

        return $ticket->fresh(['subject', 'subCategories.subject', 'sprint']);
    }

    public function complete(StudyTicket $ticket): StudyTicket
    {
        $ticket->update([
            'status'       => TicketStatus::Done->value,
            'completed_at' => Carbon::now(),
        ]);

        return $ticket->fresh(['subject', 'subCategories.subject', 'sprint']);
    }

    public function reopen(StudyTicket $ticket): StudyTicket
    {
        $ticket->update([
            'status'       => TicketStatus::Todo->value,
            'completed_at' => null,
        ]);

        return $ticket->fresh(['subject', 'subCategories.subject', 'sprint']);
    }

    public function move(StudyTicket $ticket, int $sprintId): StudyTicket
    {
        $ticket->update(['sprint_id' => $sprintId]);

        return $ticket->fresh(['subject', 'subCategories.subject', 'sprint']);
    }

    public function delete(StudyTicket $ticket): void
    {
        $ticket->delete();
    }

    public function statsBySubCategory(int $userId, int $sprintId): Collection
    {
        return StudyTicket::with('subCategories')
            ->where('user_id', $userId)
            ->where('sprint_id', $sprintId)
            ->get()
            ->flatMap(fn ($ticket) => $ticket->subCategories->map(fn ($sc) => [
                'sub_category_id'   => $sc->id,
                'sub_category_name' => $sc->name,
                'status'            => $ticket->status->value,
            ]))
            ->groupBy('sub_category_id')
            ->map(fn ($items) => [
                'subCategoryId'   => $items->first()['sub_category_id'],
                'subCategoryName' => $items->first()['sub_category_name'],
                'total'           => $items->count(),
                'done'            => $items->where('status', 'done')->count(),
            ])
            ->values();
    }
}
