<?php

namespace App\Jobs;

use App\Models\PayrollRun;
use App\Support\WebhookSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pushes a payroll journal (accrual or payment) to the Accounts app's
 * /api/ingest/journal endpoint, and records the returned reference on the run.
 * Retries with backoff; if Accounts is down the run keeps its blank ref and
 * `hr:reconcile-journals` re-pushes later.
 */
class PostJournalToAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public function __construct(
        public int $payrollRunId,
        public string $kind,   // payroll_accrual | payroll_payment
        public array $lines,
        public string $date,
        public string $narration,
    ) {
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900, 1800, 3600];
    }

    public function handle(): void
    {
        $run = PayrollRun::find($this->payrollRunId);
        if (! $run) {
            return;
        }
        $refCol = $this->kind === 'payroll_payment' ? 'payment_journal_ref' : 'accrual_journal_ref';
        if ($run->{$refCol}) {
            return; // already delivered
        }

        $base = rtrim((string) env('ACCOUNTS_BASE_URL', ''), '/');
        $secret = (string) env('INTEGRATION_ACCOUNTS_SECRET', '');
        if ($base === '' || $secret === '') {
            throw new \RuntimeException('ACCOUNTS_BASE_URL / INTEGRATION_ACCOUNTS_SECRET not set');
        }

        $body = json_encode([
            'event_id' => (string) Str::uuid(),
            'topic' => 'journal.posted',
            'data' => [
                'kind' => $this->kind,
                'date' => $this->date,
                'narration' => $this->narration,
                'source_type' => PayrollRun::class,
                'source_id' => $run->id,
                'is_manual' => false,
                'lines' => $this->lines,
            ],
        ], JSON_UNESCAPED_SLASHES);

        $res = Http::withHeaders(WebhookSignature::headers($body, $secret))
            ->withBody($body, 'application/json')
            ->timeout(15)
            ->post($base . '/api/ingest/journal');

        if (! $res->successful()) {
            throw new \RuntimeException("Accounts /api/ingest/journal → HTTP {$res->status()}: " . mb_substr($res->body(), 0, 300));
        }

        $run->forceFill([$refCol => $this->kind . ':' . now()->timestamp])->saveQuietly();
        if ($this->kind === 'payroll_payment') {
            $run->forceFill(['status' => 'posted'])->saveQuietly();
        }
    }
}
