<?php

namespace App\Infrastructure\Repositories;

use App\Domain\TicketNote\TicketNoteRepositoryInterface;
use App\Models\TicketNote;
use Illuminate\Support\Collection;

class EloquentTicketNoteRepository implements TicketNoteRepositoryInterface
{
    public function findAllByTicket(int $ticketId): Collection
    {
        return TicketNote::where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get();
    }

    public function findByIdAndTicket(int $id, int $ticketId): ?TicketNote
    {
        return TicketNote::where('ticket_id', $ticketId)->find($id);
    }

    public function create(int $ticketId, int $userId, string $body): TicketNote
    {
        return TicketNote::create([
            'ticket_id' => $ticketId,
            'user_id'   => $userId,
            'body'      => $body,
        ]);
    }

    public function update(TicketNote $note, string $body): TicketNote
    {
        $note->update(['body' => $body]);

        return $note->fresh();
    }

    public function delete(TicketNote $note): void
    {
        $note->delete();
    }
}
