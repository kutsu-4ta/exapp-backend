<?php

namespace App\Domain\TicketNote;

use App\Models\TicketNote;
use Illuminate\Support\Collection;

interface TicketNoteRepositoryInterface
{
    public function findAllByTicket(int $ticketId): Collection;

    public function findByIdAndTicket(int $id, int $ticketId): ?TicketNote;

    public function create(int $ticketId, int $userId, string $body): TicketNote;

    public function update(TicketNote $note, string $body): TicketNote;

    public function delete(TicketNote $note): void;
}
