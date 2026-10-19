# Payout patches — auto-reverse on failure + separate driver/restaurant days

These are **edits to existing files** (not drop-in classes). Apply by hand, then
`php artisan config:clear && php artisan schedule:clear-cache` (if used).

---

## 1. Auto-reverse locked wallet funds whenever a payout fails

**Current behaviour:** at generation `PayoutCalculationService` locks the net
amount (`balance -= net`, `locked_balance += net`, `source='scheduled'`).
`PayoutSettlementService::settleFromGatewayResult()` already reverses that on a
`failed` settle — **but** two branches in `BulkPayoutService::process()` bail out
with `continue` *before* settle runs and leave the payout `pending` with funds
still locked. Legacy (`source === null`) payouts are also skipped by the guard in
`releaseLockedFundsIfNeeded()`.

### 1a. `app/Services/PayoutSettlementService.php` — `releaseLockedFundsIfNeeded()`

```diff
     public function releaseLockedFundsIfNeeded(Payout $payout, bool $restoreBalance = false): void
     {
-        if ($payout->source === null) {
+        // Only skip when there is genuinely nothing to undo. A failure reversal
+        // ($restoreBalance = true) must always run, including for legacy
+        // (null-source) payouts, so money never stays stuck in locked_balance.
+        if ($payout->source === null && ! $restoreBalance) {
             return;
         }
```

### 1b. `app/Services/BulkPayoutService.php` — release + mark failed in the early-exit branches

```diff
             $provider = $payout->gateway ?: PayoutSetting::activeGateway();
             if (!$this->gatewayService->supportsAutomatedProcessing($provider)) {
+                $this->settlementService->releaseLockedFundsIfNeeded(
+                    $payout->loadMissing(['restaurant.owner', 'driver']), true
+                );
+                $payout->update([
+                    'status' => 'failed',
+                    'failure_reason' => $this->gatewayService->unsupportedAutomationMessage($provider),
+                ]);
                 $report['failed']++;
                 $report['items'][$payout->id] = [
                     'success' => false,
                     'error' => $this->gatewayService->unsupportedAutomationMessage($provider),
                 ];
                 continue;
             }

             if (! $this->settlementService->ensureFundsReserved(
                 $payout->loadMissing(['restaurant.owner', 'driver'])
             )) {
+                // Don't clobber a payout a concurrent worker already advanced.
+                $fresh = $payout->fresh();
+                if ($fresh && $fresh->status === 'pending') {
+                    $this->settlementService->releaseLockedFundsIfNeeded(
+                        $payout->loadMissing(['restaurant.owner', 'driver']), true
+                    );
+                    $payout->update([
+                        'status' => 'failed',
+                        'failure_reason' => 'Insufficient wallet balance to reserve payout.',
+                    ]);
+                }
                 $report['failed']++;
                 $report['items'][$payout->id] = [
                     'success' => false,
                     'error' => 'Payout was already claimed or has insufficient wallet balance.',
                 ];
                 continue;
             }
```

> The `catch (\Throwable $e)` block already does the right thing (release + status
> `failed`); no change needed there.

### 1c. One-off + recurring sweep for already-stranded locks

`app/Console/Commands/ReleaseStrandedPayoutLocks.php` is included in this folder.
It finds `failed` / `cancelled` payouts whose payee wallet still has
`locked_balance > 0` with no reversal `WalletTransaction`, and releases them back
to spendable balance.

Register in `routes/console.php`:

```php
Schedule::command('payouts:release-stranded-locks')
    ->when(fn () => $cronTaskEnabled('release_stranded_payout_locks'))
    ->hourly();
```

Run once now to clear the current backlog (wallets that already show a stuck
`locked_balance`):

```bash
php artisan payouts:release-stranded-locks --since=90
```

---

## 2. Separate payout days for restaurants vs drivers  (no migration)

`payout_settings` has an `options` JSON column — use it for per-vendor schedule
overrides. One active row stays the default; `options` narrows per vendor type.

### 2a. `app/Services/PayoutScheduleService.php`

```diff
-    public function shouldRunToday(?PayoutSetting $setting = null): bool
+    public function shouldRunToday(?PayoutSetting $setting = null, ?string $vendorType = null): bool
     {
         $setting ??= PayoutSetting::where('is_active', true)->first();
-        $frequency = $setting?->schedule_frequency ?: AppSetting::getValue('payout_frequency', 'weekly');
-        $day = strtolower($setting?->schedule_day ?: AppSetting::getValue('payout_day', 'monday'));
+
+        $options  = is_array($setting?->options) ? $setting->options : [];
+        $override = $vendorType
+            ? (array) ($options[$vendorType] ?? $options[$vendorType . '_schedule'] ?? [])
+            : [];
+
+        $frequency = $override['schedule_frequency']
+            ?: $setting?->schedule_frequency
+            ?: AppSetting::getValue('payout_frequency', 'weekly');
+        $day = strtolower(
+            $override['schedule_day']
+            ?: $setting?->schedule_day
+            ?: AppSetting::getValue('payout_day', 'monday')
+        );

         return match ($frequency) {
             'daily' => true,
             'weekly' => strtolower(now()->format('l')) === $day,
             'biweekly' => now()->weekOfYear % 2 === 0 && strtolower(now()->format('l')) === $day,
             'monthly' => now()->isSameDay(now()->copy()->startOfMonth()),
             default => false,
         };
     }
+
+    /** Vendor types (`restaurant`, `driver`) whose payout run is due today. */
+    public function dueVendorTypesToday(?PayoutSetting $setting = null): array
+    {
+        $setting ??= PayoutSetting::where('is_active', true)->first();
+
+        return collect(['restaurant', 'driver'])
+            ->filter(fn ($type) => $this->shouldRunToday($setting, $type))
+            ->values()
+            ->all();
+    }
```

### 2b. `app/Console/Commands/GeneratePayouts.php` — `handle()`

```diff
         $setting = null;
+        $dueTypes = [$this->option('type')];           // manual run: all | restaurant | driver
         if ($this->option('auto')) {
             $setting = PayoutSetting::where('is_active', true)->first();
-            if (! $setting?->auto_generate_enabled || ! $service->shouldRunToday($setting)) {
-                $this->info('Automatic payout generation is not due.');
+            if (! $setting?->auto_generate_enabled) {
+                $this->info('Automatic payout generation is disabled.');
+                return self::SUCCESS;
+            }
+            $dueTypes = $service->dueVendorTypesToday($setting);
+            if (empty($dueTypes)) {
+                $this->info('Automatic payout generation is not due for any vendor type today.');
                 return self::SUCCESS;
             }
         }
@@
-        $result = $service->generateDuePayouts(
-            $this->option('type'),
-            $start,
-            $end,
-            (bool) $this->option('auto')
-        );
-        $this->info("Generated {$result['created']} payouts in batch {$result['batch_id']}.");
+        $total = 0;
+        $batches = [];
+        foreach ($dueTypes as $type) {
+            $result = $service->generateDuePayouts($type, $start, $end, (bool) $this->option('auto'));
+            $total += $result['created'];
+            $batches[] = $result['batch_id'];
+        }
+        $this->info("Generated {$total} payouts (" . implode(', ', $batches) . ").");
         return self::SUCCESS;
```

### 2c. Configure the days

The `payouts:generate --auto` cron already runs **daily at 02:00**. Set the
per-vendor days on the active `payout_settings` row (id 3 on prod is
`weekly / monday`, `is_active` currently `0` — activate the row you actually use):

```php
$s = App\Models\PayoutSetting::where('is_active', true)->first();
$s->options = array_merge($s->options ?? [], [
    'restaurant' => ['schedule_frequency' => 'weekly', 'schedule_day' => 'monday'],
    'driver'     => ['schedule_frequency' => 'weekly', 'schedule_day' => 'thursday'],
]);
$s->save();
```

Result: restaurants generate Monday 02:00, drivers Thursday 02:00. Omit a vendor
key to fall back to the row's top-level `schedule_day`.

---

## Alternative reading of "do not lock"

If you'd rather **not lock at generation at all** and instead debit the wallet
only at settle time: in `PayoutCalculationService` (~line 392) drop the
`wallet->decrement('balance') / increment('locked_balance')` pair for
`source='scheduled'`, and have `BulkPayoutService` call `reserveFunds()`
unconditionally right before `gatewayService->process()` (it already does via
`ensureFundsReserved` when under-reserved). That removes the "stuck locked
balance" failure mode entirely but weakens the guarantee that a scheduled payout
is fully funded when it's created. The patch above is the lower-risk option.
