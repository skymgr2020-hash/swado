<?php

namespace App\Support;

use App\Models\ProcessedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared inbound-webhook helper: verify the HMAC and run the handler exactly
 * once per event_id.
 */
class InboundWebhook
{
    public static function verify(Request $request, string $secret): bool
    {
        return WebhookSignature::verify($request, $secret);
    }

    /** @return array{handled:bool, result:mixed} */
    public static function once(string $eventId, string $topic, \Closure $fn): array
    {
        if (ProcessedEvent::where('event_id', $eventId)->exists()) {
            return ['handled' => false, 'result' => null];
        }

        return DB::transaction(function () use ($eventId, $topic, $fn) {
            $result = $fn();
            ProcessedEvent::create(['event_id' => $eventId, 'topic' => $topic, 'processed_at' => now()]);

            return ['handled' => true, 'result' => $result];
        });
    }
}
