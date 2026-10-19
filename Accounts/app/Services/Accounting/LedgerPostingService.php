<?php

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\TaxLedgerEntry;
use App\Services\Tax\TaxConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Posts balanced double-entry journals for the platform's money events.
 * Every poster is idempotent on (source_type, source_id, kind) and a no-op
 * until the general ledger is switched on (accounting_enabled).
 */
class LedgerPostingService
{
    private array $accountCache = [];

    public function __construct(private readonly TaxConfig $config = new TaxConfig())
    {
    }

    public function enabled(): bool
    {
        return true;
    }

    /* ================= primitive ================= */

    /**
     * @param  array<int,array{account:string,debit?:float,credit?:float,party_type?:string,party_id?:int,memo?:string}>  $lines
     */
    public function post(array $lines, string $narration, Carbon|string|null $date = null, array $source = [], bool $isManual = false): ?JournalEntry
    {
        if (! $this->enabled() && ! $isManual) {
            return null;
        }

        $lines = array_values(array_filter($lines, fn ($l) => round((float) ($l['debit'] ?? 0) + (float) ($l['credit'] ?? 0), 2) > 0));
        if (count($lines) < 2) {
            return null;
        }

        $debit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $credit = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 2);
        if ($debit !== $credit) {
            Log::warning('Ledger post rejected: unbalanced.', compact('narration', 'debit', 'credit'));

            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date ?: now());
        $period = $date->format('Y-m');
        if (AccountingPeriod::isClosed($period)) {
            Log::info('Ledger post skipped: period closed.', compact('period', 'narration'));

            return null;
        }

        $sourceType = $source['type'] ?? null;
        $sourceId = $source['id'] ?? null;
        $kind = $source['kind'] ?? ($isManual ? 'manual' : null);

        return DB::transaction(function () use ($lines, $narration, $date, $period, $sourceType, $sourceId, $kind, $isManual, $debit) {
            if ($sourceType && $sourceId && $kind) {
                $existing = JournalEntry::where('source_type', $sourceType)->where('source_id', $sourceId)->where('kind', $kind)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $fy = $this->config->fy($date);
            $entry = JournalEntry::create([
                'entry_no' => $this->nextEntryNo($fy),
                'date' => $date->toDateString(),
                'narration' => $narration,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'kind' => $kind,
                'fy' => $fy,
                'period' => $period,
                'status' => 'posted',
                'is_manual' => $isManual,
                'posted_by' => auth()->id(),
            ]);

            foreach ($lines as $l) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->accountId($l['account']),
                    'debit' => round((float) ($l['debit'] ?? 0), 2),
                    'credit' => round((float) ($l['credit'] ?? 0), 2),
                    'party_type' => $l['party_type'] ?? null,
                    'party_id' => $l['party_id'] ?? null,
                    'memo' => $l['memo'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    public function postManualJournal(array $lines, string $narration, string $date): ?JournalEntry
    {
        return $this->post($lines, $narration, $date, [], true);
    }

    /* ================= helpers ================= */

    private function accountId(string $code): int
    {
        return $this->accountCache[$code] ??= (int) (ChartOfAccount::byCode($code)?->id
            ?? ChartOfAccount::byCode('1900')?->id
            ?? ChartOfAccount::query()->value('id'));
    }

    private function nextEntryNo(string $fy): string
    {
        $n = JournalEntry::where('fy', $fy)->count() + 1;

        return 'JV/' . $fy . '/' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
