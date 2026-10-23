<?php

namespace App\Domain;

use App\Models\Journal;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Ledger
{
    /** Lines: account => signed debit in paisa; credits negative. */
    public function post(int $branch, string $key, string $description, array $lines): Journal
    {
        if (array_sum($lines) !== 0) {
            throw ValidationException::withMessages(['ledger' => 'Unbalanced journal.']);
        }

        return DB::transaction(function () use ($branch, $key, $description, $lines) {
            if ($existing = Journal::where('event_key', $key)->first()) {
                return $existing;
            }
            $j = Journal::create(['branch_id' => $branch, 'event_key' => $key, 'description' => $description, 'user_id' => auth()->id()]);
            foreach ($lines as $account => $amount) {
                if (! $amount) {
                    continue;
                }
                LedgerEntry::create(['branch_id' => $branch, 'journal_id' => $j->id, 'account' => $account, 'debit' => max(0, $amount), 'credit' => max(0, -$amount)]);
            }

            return $j;
        });
    }
}
