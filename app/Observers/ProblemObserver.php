<?php

namespace App\Observers;

use App\Models\KnowledgeDigest;
use App\Models\Problem;
use App\Services\NoteParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProblemObserver
{
    public function created(Problem $problem): void
    {
        $this->syncDigest($problem);
    }

    public function updated(Problem $problem): void
    {
        if (!$problem->wasChanged('note')) {
            return;
        }
        $this->syncDigest($problem);
    }

    private function syncDigest(Problem $problem): void
    {
        $parsed = NoteParser::parse($problem->note);

        if (empty($parsed)) {
            return;
        }

        try {
            KnowledgeDigest::updateOrCreate(
                ['problem_id' => $problem->id],
                [
                    'definitions'  => isset($parsed['definition'])  ? [$parsed['definition']]  : null,
                    'formulas'     => isset($parsed['formula'])      ? [$parsed['formula']]      : null,
                    'keywords'     => isset($parsed['keyword'])      ? [$parsed['keyword']]      : null,
                    'pitfalls'     => isset($parsed['pitfall'])      ? [$parsed['pitfall']]      : null,
                    'examples'     => isset($parsed['example'])      ? [$parsed['example']]      : null,
                    'relations'    => isset($parsed['relation'])     ? [$parsed['relation']]     : null,
                    'memory_hooks' => isset($parsed['memory_hook'])  ? [$parsed['memory_hook']]  : null,
                    'extracted_at' => Carbon::now(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('KnowledgeDigest sync failed on note change', [
                'problem_id' => $problem->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
