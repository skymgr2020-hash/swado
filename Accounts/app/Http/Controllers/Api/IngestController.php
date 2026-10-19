<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtOrder;
use App\Models\ExtPayout;
use App\Models\TaxLedgerEntry;
use App\Models\User;
use App\Services\Accounting\LedgerPostingService;
use App\Support\InboundWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Everything the Accounts app receives from admin/ and HRMS/. Signed with an
 * HMAC shared secret; replays are absorbed by the processed_events table.
 */
class IngestController extends Controller
{
    public function __construct(private readonly LedgerPostingService $ledger)
    {
    }

    /** journal.posted — a balanced entry admin/ or HRMS/ already posted. */
    public function journal(Request $request)
    {
        $this->auth($request, ['admin', 'hrms']);
        [$eventId, $topic, $d] = $this->envelope($request);

        InboundWebhook::once($eventId, $topic, function () use ($d) {
            // optional mirror row (orders / payouts) for the GST read models
            if ($m = ($d['mirror'] ?? null)) {
                $this->applyMirror($m);
            }

            $lines = collect($d['lines'] ?? [])
                ->map(fn ($l) => [
                    'account' => $l['code'] ?? $l['account'] ?? null,
                    'debit' => (float) ($l['debit'] ?? 0),
                    'credit' => (float) ($l['credit'] ?? 0),
                    'party_type' => $l['party_type'] ?? null,
                    'party_id' => $l['party_id'] ?? null,
                    'memo' => $l['memo'] ?? null,
                ])->all();

            if (count($lines) < 2) {
                return;
            }

            $this->ledger->post(
                $lines,
                $d['narration'] ?? ($d['kind'] ?? 'journal'),
                $d['date'] ?? now()->toDateString(),
                [
                    'type' => $d['source_type'] ?: 'ext',
                    'id' => $d['source_id'] ?: (int) (Str::of($d['entry_no'] ?? '')->afterLast('/')),
                    'kind' => $d['kind'] ?? 'journal',
                ],
                (bool) ($d['is_manual'] ?? false),
            );
        });

        return response()->json(['ok' => true]);
    }

    /** tax.accrued (a TaxLedgerEntry row) or ping. */
    public function ledgerEvent(Request $request)
    {
        $this->auth($request, ['admin']);
        [$eventId, $topic, $d] = $this->envelope($request);

        if ($topic === 'ping') {
            return response()->json(['ok' => true, 'pong' => now()->toIso8601String()]);
        }

        InboundWebhook::once($eventId, $topic, function () use ($d) {
            TaxLedgerEntry::updateOrCreate(
                ['id' => $d['id']],
                collect($d)->only([
                    'kind', 'section', 'order_id', 'payout_id', 'party_type', 'party_id',
                    'taxable_value', 'rate', 'cgst', 'sgst', 'igst', 'amount', 'fy', 'period', 'status', 'meta',
                ])->all() + ['created_at' => $d['created_at'] ?? now()],
            );
        });

        return response()->json(['ok' => true]);
    }

    /** user.upserted / user.deleted — identity sync from admin/. */
    public function user(Request $request)
    {
        $this->auth($request, ['admin']);
        [$eventId, $topic, $d] = $this->envelope($request);

        InboundWebhook::once($eventId, $topic, function () use ($topic, $d) {
            if ($topic === 'user.deleted') {
                User::where('email', $d['email'])->update(['is_active' => false]);

                return;
            }

            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'phone' => $d['phone'] ?? null,
                    'password' => $d['password'] ?? Hash::make(Str::random(32)),
                    'is_active' => (bool) ($d['is_active'] ?? true),
                ]
            );
            $user->syncRoles(array_values(array_intersect($d['roles'] ?? [], ['accountant', 'admin', 'super_admin'])) ?: ['accountant']);
        });

        return response()->json(['ok' => true]);
    }

    /* ---------------- helpers ---------------- */

    private function auth(Request $request, array $sources): void
    {
        foreach ($sources as $src) {
            $secret = (string) env('INTEGRATION_' . strtoupper($src) . '_SECRET', '');
            if ($secret !== '' && InboundWebhook::verify($request, $secret)) {
                return;
            }
        }
        abort(401, 'bad signature');
    }

    private function envelope(Request $request): array
    {
        return [
            (string) $request->input('event_id', (string) Str::uuid()),
            (string) $request->input('topic', 'unknown'),
            (array) $request->input('data', []),
        ];
    }

    private function applyMirror(array $m): void
    {
        $row = $m['row'] ?? [];
        if (($m['table'] ?? '') === 'ext_orders' && ! empty($row['id'])) {
            if (isset($row['tax_breakdown']) && is_string($row['tax_breakdown'])) {
                $row['tax_breakdown'] = json_decode($row['tax_breakdown'], true);
            }
            ExtOrder::updateOrCreate(['id' => $row['id']], collect($row)->except('id')->all());
        } elseif (($m['table'] ?? '') === 'ext_payouts' && ! empty($row['id'])) {
            ExtPayout::updateOrCreate(['id' => $row['id']], collect($row)->except('id')->all());
        }
    }
}
