<?php

namespace App\Domain\StudyTicket;

use App\Models\StudyTicket;
use Illuminate\Support\Collection;

interface StudyTicketRepositoryInterface
{
    public function findAllByUser(int $userId, ?int $sprintId = null, ?string $status = null): Collection;

    public function findByIdAndUser(int $id, int $userId): ?StudyTicket;

    public function create(int $userId, array $data, array $subCategoryIds): StudyTicket;

    public function update(StudyTicket $ticket, array $data, ?array $subCategoryIds = null): StudyTicket;

    public function complete(StudyTicket $ticket): StudyTicket;

    public function reopen(StudyTicket $ticket): StudyTicket;

    public function move(StudyTicket $ticket, int $sprintId): StudyTicket;

    public function delete(StudyTicket $ticket): void;

    public function statsBySubCategory(int $userId, int $sprintId): Collection;
}
