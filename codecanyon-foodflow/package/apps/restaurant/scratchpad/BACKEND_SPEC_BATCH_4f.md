# Backend spec — AI assistant + menu analysis + invoices + notification test

All under `Route::middleware('auth:sanctum')->prefix('restaurant')`, resolved for the
authenticated restaurant owner (`getAuthenticatedRestaurant($user)` pattern).

---

## 1. AI growth assistant

**Key source — VERIFIED on prod:** the OpenAI key IS already configured in the admin
**AI Control Center** (`admin/ai` → `AiControlCenterController`). Read it via the existing
`App\Services\Ai\AiSettingsService`:
```php
$ai = app(\App\Services\Ai\AiSettingsService::class);
$ai->health();            // ['enabled'=>true, 'kill_switch'=>false, 'openai_configured'=>true, 'provider'=>'openai', ...]
$ai->get('openai_api_key');   // encrypted, currently SET (164 chars)
$ai->get('ai_provider');      // 'openai'   $ai->get('openai_model')  // 'gpt-4o-mini'
\App\Models\AiSetting::getValue('ai_kill_switch');  // false
```
Do NOT re-implement the OpenAI HTTP call — use the same router the admin chat uses:
`app(\App\Services\Ai\AiCostAwareRouter::class)` (in `app/Services/Ai/`). It reads the
AI-Control-Center key, handles provider + gemini↔openai fallback, cost tracking and the
kill-switch. `admin/ai/chat` → `AiControlCenterController@chatAsk` is a working reference.

> Note: `GET /api/ai/status` (VoiceAiConfigController) is the **voice** AI flag and is
> unrelated — it returns `enabled:false` even though the text AI is configured. Ignore it
> for this feature; the app now calls `/restaurant/assistant/status` instead.

### `GET /api/restaurant/assistant/status`
`{ "success": true, "data": { "enabled": true } }`
`enabled` = `AiSettingsService::health()['enabled'] && !kill_switch && (openai_configured || gemini_configured)`.
The app calls this on open; when it 404s the app stays optimistic and lets `message` surface
the real error.

### `POST /api/restaurant/assistant/message`
Body: `{ "message": "string (1..2000)", "conversation_id": "uuid?" }`

Flow:
1. Guard: if not enabled (see status) → `{ success:false, message:"The AI assistant isn't
   switched on. Enable it in Admin → AI Control Center." }` (app shows this text).
2. Load / create a conversation row (`ai_assistant_conversations` keyed by user+restaurant).
3. Build a **context blob** from live data (last 30 days unless the user asks otherwise):
   - from `restaurant/analytics`: total_revenue, total_orders, avg_order_value,
     cancelled_orders, cancellation_rate, daily_revenue, top_items, hourly_data
   - from `restaurant/statements`: net, commission, tds, pending payout, last cycle
   - from promos: active offers count; from ads: active campaigns + spend
   - restaurant profile: name, city, is_open, weekly_timings, rating
   (`App\Services\Ai\AiBusinessSnapshotService` may already assemble most of this.)
4. System prompt: "You are a growth advisor for an Indian restaurant on a food-delivery
   platform. Be specific and numeric. Always answer with (a) a short reply and (b) one or
   more step-by-step plans." Pass the context blob + the user message + prior turns; ask for
   JSON (`{reply, plan:[{title,steps}], suggestions:[]}`).
5. Send through `AiCostAwareRouter`; persist both turns.

Response:
```json
{ "success": true, "data": {
  "conversation_id": "uuid",
  "reply": "short markdown-ish text",
  "plan": [
    { "title": "Increase weekday lunch orders", "steps": ["…","…","…"] }
  ],
  "suggestions": ["Design the coupon", "Show me last month"]   // optional follow-up chips
}}
```

### `GET /api/restaurant/assistant/history`
`{ success, data: { conversation_id, messages: [ { role: "user"|"assistant", text, plan? } ] } }`
Return the latest conversation (or empty). App renders it on open.

Rate-limit e.g. `throttle:20,1`.

---

## 2. Menu analysis

### `GET /api/restaurant/menu/analytics?days=30`
Aggregate `order_items` joined to `menu_items` for this restaurant over the window.

```json
{ "success": true, "data": {
  "days": 30,
  "items": [
    { "id": 12, "name": "Veg Thali", "qty": 84, "revenue": 12516,
      "trend": [2,3,1,4,...],          // daily qty, oldest→newest (for a sparkline, optional)
      "group": "best",                  // "best" (top third by qty) | "low" (bottom third) | "mid"
      "ai_suggestion": "Your top seller — bundle it with a drink to lift AOV."  // optional; only if AI enabled
    }
  ]
}}
```
- Include **every** menu item that was ordered at least once in the window; optionally also
  items with 0 qty so the restaurant sees dead stock (group them "low").
- `ai_suggestion`: if the AI service is enabled, batch one short line per item (best sellers →
  "promote/bundle", low sellers → "reprice / photo / remove / feature"). If AI is off, omit
  the field — the app shows "suggestions appear once AI is enabled".
- The app falls back to `restaurant/analytics` `top_items` when this endpoint 404s, so it
  degrades gracefully, but that fallback only has the top handful with `total_orders`.

---

## 3. Invoices & downloads

App reads `GET /api/restaurant/statements` today for the net/commission/TDS/payout summary
and `fy_tds_194o.form_16a_url` (currently null → wire the Form 16A PDF URL).

### `GET /api/restaurant/invoices?type=ordering|ads[&month=YYYY-MM]`
```json
{ "success": true, "data": { "invoices": [
  { "id": "inv_2026_08", "label": "Online ordering — Aug 2026",
    "period": "01 Aug – 31 Aug 2026", "amount": 8421.00,
    "download_url": "https://…/invoices/…pdf" }
]}}
```
- `type=ordering` → month-wise platform/commission invoice for online orders.
- `type=ads` → month-wise ad-spend invoice (from the ad wallet ledger).
- Also make sure each `cycles[]` entry in `/statements` carries a `download_url` (or
  `statement_url`) for the per-cycle payout statement PDF — the app already looks for it.

`download_url` should be a real PDF (S3 or a signed route). The app opens it with
`launchUrl(externalApplication)`.

---

## 4. Test order notification

### `POST /api/restaurant/notifications/test`
- Look up every FCM token registered for this owner (the `user/fcm-token` store,
  `target_app = restaurant`).
- Send a push shaped exactly like a real new-order alert (same `data` keys the app's
  `IncomingOrderAlertService` expects, but with `test: true` and a fake order id like
  `TEST1234`) so the full alert path (sound, full-screen, foreground service) is exercised.
- Response: `{ "success": true, "data": { "sent_to": 2 } }`
- The app also fires a local preview notification regardless, so this endpoint is a
  best-effort "prove it reaches the device when the app is closed" test.

---

## 5. Failed / stuck payout → auto-reverse locked funds (do not leave locked)

### Current state (verified on prod)
- Scheduled restaurant/driver payouts **lock funds on creation**:
  `PayoutCalculationService` ~L392 `wallet.balance -= net; wallet.locked_balance += net`
  (`source='scheduled'`, payout `status='pending'`).
- On `status='failed'` the code DOES reverse: `PayoutSettlementService::releaseLockedFundsIfNeeded($payout, true)`
  is called from `settleFromGatewayResult`, `settleFromStatusPayload`, and the
  `BulkPayoutService` catch block.
- **The gap:** several non-success outcomes never set `status='failed'` and never release:
  1. `BulkPayoutService::process` — `!supportsAutomatedProcessing($provider)` branch (~L27)
     and the `ensureFundsReserved` false branch (~L36): they only bump `report['failed']`,
     leaving the payout **`pending` with funds still locked**.
  2. A payout stuck in `processing` (gateway accepted, webhook/status-sync never confirms):
     **no timeout sweep** — locked forever.
  3. `releaseLockedFundsIfNeeded` early-returns on `if ($payout->source === null) return;`
     — any null-source payout (e.g. some manual withdrawals) that fails is never released.
- **Live proof:** 2 payouts, both `pending`; wallet 2 (owner) `balance 0 / locked 308`,
  wallet 6 (driver) `balance 10 / locked 76`. 0 `failed` payouts. Funds are stranded.

### Fix
1. **`PayoutSettlementService::releaseLockedFundsIfNeeded`**
   - Drop the `if ($payout->source === null) return;` guard (or replace with
     `if (! $this->payeeForPayout($payout)) return;`).
   - Make it idempotent: skip if a release WalletTransaction already exists for
     `reference_type='payout' AND reference_id=$payout->id AND type='credit'`, or add a
     `payouts.locked_released_at` column and set it.
   - Generic description: `'Payout failed — reserved funds released'`.

2. **`BulkPayoutService::process`** — in BOTH early-exit branches
   (`!supportsAutomatedProcessing` and `ensureFundsReserved === false`), before
   `continue;`:
   ```php
   $payout->update(['status' => 'failed', 'failure_reason' => $msg]);
   $this->settlementService->releaseLockedFundsIfNeeded(
       $payout->loadMissing(['restaurant.owner', 'driver']), true
   );
   ```

3. **New timeout sweep** — `php artisan payouts:release-stuck` (schedule every 15 min):
   ```php
   Payout::whereIn('status', ['pending','processing'])
       ->where('updated_at', '<', now()->subHours(6))
       ->get()
       ->each(function (Payout $p) use ($settlement) {
           $p->update(['status' => 'failed',
               'failure_reason' => 'Auto-failed: no gateway confirmation within 6h']);
           $settlement->releaseLockedFundsIfNeeded(
               $p->loadMissing(['restaurant.owner','driver']), true);
       });
   ```
   (`SyncPayoutStatus` should still try a real status check first; this is the backstop.)

4. **One-off cleanup for the 2 stranded payouts** (tinker / migration):
   for each `pending` payout with locked funds, run
   `app(PayoutSettlementService::class)->releaseLockedFundsIfNeeded($payout, true)` and set
   `status='failed', failure_reason='Released — legacy stuck reservation'`.

5. **Optional — "do not lock" at all for scheduled payouts:** instead of
   `balance -= net; locked_balance += net` at creation, leave `balance` untouched and only
   `debitWalletIfNeeded` on `status='completed'`. Then failure needs no reversal. Trade-off:
   the restaurant could withdraw the same money while a payout is in-flight — mitigate by
   subtracting `pending payout total` from the withdrawable amount shown/allowed in
   `WalletController`. If you keep locking, steps 1–4 make it self-healing.

---

## 6. Separate payout days for drivers vs restaurants

### Current state (verified)
- ONE `payout_settings` row is active (#1: `weekly` / `monday`). No `vendor_type` column.
- Cron: `payouts:generate --auto` runs `dailyAt('02:00')`.
- `PayoutScheduleService::shouldRunToday()` reads the single setting's
  `schedule_frequency` + `schedule_day` → generates on that one day.
- `GeneratePayouts::handle` calls `generateDuePayouts('all', …)` which aggregates
  **restaurant AND driver payouts in the same batch on the same day**.

### Fix (no migration needed — use `AppSetting` keys or the setting's `options` JSON)
1. **Config keys**
   - `payout_day` (restaurant, existing, default `monday`), `payout_frequency` (existing).
   - New: `payout_day_driver` (default e.g. `thursday`), `payout_frequency_driver`
     (default = `payout_frequency`). Add a second day/frequency picker to the admin
     Payout-settings screen. (Or store as `payout_settings.options->driver_schedule_day` /
     `->driver_schedule_frequency`.)

2. **`PayoutScheduleService::shouldRunToday`** — add `string $vendorType = 'restaurant'`:
   ```php
   public function shouldRunToday(?PayoutSetting $setting = null, string $vendorType = 'restaurant'): bool
   {
       $setting ??= PayoutSetting::where('is_active', true)->first();
       if ($vendorType === 'driver') {
           $frequency = $setting?->options['driver_schedule_frequency']
               ?? AppSetting::getValue('payout_frequency_driver',
                    AppSetting::getValue('payout_frequency', 'weekly'));
           $day = strtolower($setting?->options['driver_schedule_day']
               ?? AppSetting::getValue('payout_day_driver', 'thursday'));
       } else {
           $frequency = $setting?->schedule_frequency ?: AppSetting::getValue('payout_frequency', 'weekly');
           $day = strtolower($setting?->schedule_day ?: AppSetting::getValue('payout_day', 'monday'));
       }
       return match ($frequency) { /* same match block as today */ };
   }
   ```

3. **`GeneratePayouts::handle`** (`--auto` path) — generate each vendor type only on its day:
   ```php
   $end = now()->subDay()->endOfDay();
   $ran = 0;
   foreach (['restaurant', 'driver'] as $vt) {
       if (! $service->shouldRunToday($setting, $vt)) continue;
       $freq = $vt === 'driver'
           ? ($setting?->options['driver_schedule_frequency'] ?? $setting?->schedule_frequency)
           : $setting?->schedule_frequency;
       $start = match ($freq) {
           'weekly'   => Carbon::parse($end)->copy()->subDays(6)->startOfDay(),
           'biweekly' => Carbon::parse($end)->copy()->subDays(13)->startOfDay(),
           'monthly'  => Carbon::parse($end)->copy()->startOfMonth(),
           default    => Carbon::parse($end)->copy()->startOfDay(),
       };
       $r = $service->generateDuePayouts($vt, $start, $end, true);
       $this->info("[$vt] generated {$r['created']} in {$r['batch_id']}");
       $ran++;
   }
   if ($ran === 0) $this->info('Neither vendor type is due today.');
   ```
   Manual runs (`payouts:generate --type=driver` / `--type=restaurant`) already work and are
   unchanged.

4. **Nothing else changes** — `payouts:process-scheduled`, `payouts:sync-status`,
   `payouts:retry-failed` process whatever `pending` payouts exist regardless of vendor type;
   `PayoutCalculationService` already tags each payout with `restaurant_id` / `driver_id`.

---

## 7. Restaurant creation & multi-outlet — BUG: one owner can never own >1 restaurant

### How it works today (verified)
1. App register wizard (`register_screen.dart`, 4 steps) → `POST /api/partner-applications`
   (`Api\PartnerApplicationController@submit`) → a `PartnerApplication` row
   (`partner_type=restaurant`, contact_* + business_* + bank + verified_phone_token).
2. App polls `GET /api/partner-applications/{applicationNumber}` for status.
3. Admin approves → `Admin\PartnerApplicationController@approveRestaurantApplication()`:
   - **`$owner = User::create([...])` — UNCONDITIONAL.** No lookup of an existing user by
     phone/email.
   - `$owner->assignRole('restaurant_owner')`
   - `Restaurant::create(['owner_id' => $owner->id, 'state' => 'N/A' /* hardcoded */, ...])`
   - `$owner->update(['current_restaurant_id' => $restaurant->id])` (unconditional)
4. Multi-outlet read path: `GET /api/restaurant/restaurants` → `myRestaurants` →
   `getAccessibleRestaurants($user)` → **`$user->restaurants()` = `hasMany(Restaurant,'owner_id')`**
   → only restaurants whose `owner_id === user.id`. The app switcher + "All outlets" mode
   work fine **once one user owns >1 restaurant** — they just never do.

### The bug
Because approve always `User::create`s a fresh owner, **the same person applying for a 2nd
restaurant gets a 2nd login**, and each login sees exactly one outlet.
Live proof: `r1 "Sri Krishna Thali" owner_id=8` (karn.goswami@gmail.com / +919279380225),
`r2 "Madhav Pure Veg" owner_id=9` (renjodelivery@gmail.com / +916202531748) — both users
named "karn Goswami", and phones `+916202531748` & `+919279380225` each appear on **2 user
rows**, email `renjodelivery@gmail.com` on 2. There is also **no "add another outlet to my
account" path** — the wizard always collects fresh owner details and never sends the
logged-in owner's id/token.
Secondary: `state => 'N/A'` hardcoded; no `firstOrCreate` so a shared email also risks the
unique constraint.

### Fix
**A. `approveRestaurantApplication` — reuse the existing owner:**
```php
$owner = User::where('phone', $application->contact_phone)
    ->when($application->contact_email, fn ($q) =>
        $q->orWhere('email', $application->contact_email))
    ->first()
    ?? User::create([ /* current fields */ ]);

if (! $owner->hasRole('restaurant_owner')) $owner->assignRole('restaurant_owner');
// backfill bank fields only when the existing owner's are empty — never clobber

$restaurant = Restaurant::create([
    'owner_id' => $owner->id,
    'state'    => $application->state ?: 'N/A',   // stop hardcoding
    // ...rest unchanged...
]);

if (is_null($owner->current_restaurant_id)) {
    $owner->update(['current_restaurant_id' => $restaurant->id]);
}
```

**B. App wizard — "Add another outlet" mode** when opened by a logged-in owner:
prefill owner name/email/phone/bank from the session, and include `existing_owner_user_id`
(or just rely on the sanctum token) in the `POST /api/partner-applications` body so approve
links `owner_id` to that user with no guessing.

**C. One-off prod data merge** (so the current person sees both outlets from one login):
```sql
UPDATE restaurants SET owner_id = 8 WHERE id = 2;   -- point r2 at the login they actually use
-- then reassign / soft-delete the now-orphan user 9, and dedupe the 2 rows sharing
--   renjodelivery@gmail.com and the 2 rows each sharing +916202531748 / +919279380225.
```
After B/C, `myRestaurants` returns both and the outlet switcher just works.
