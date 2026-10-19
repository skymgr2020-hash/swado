# backend_4f — restaurant app: AI assistant, menu analytics, invoices, notif test

Plain source files. Copy them into any Swado Laravel deployment by hand — the
folder mirrors the Laravel root, so each file goes to the same relative path.

## 1. Copy these files (new — no conflicts)

```
app/Http/Controllers/Api/Restaurant/RestaurantAssistantController.php
app/Http/Controllers/Api/Restaurant/MenuAnalyticsController.php
app/Http/Controllers/Api/Restaurant/RestaurantInvoicesController.php
app/Http/Controllers/Api/Restaurant/NotificationTestController.php
app/Http/Controllers/Api/Restaurant/Concerns/ResolvesRestaurantScope.php
app/Console/Commands/ReleaseStrandedPayoutLocks.php
database/migrations/2026_09_04_100000_create_restaurant_ai_messages_table.php
```

## 2. Edit `routes/api.php`

Open `routes/api.php.snippet` and:

- add its 4 `use App\Http\Controllers\Api\Restaurant\...;` lines with the other
  `use` imports at the top of `routes/api.php`;
- paste its route block **inside** the existing
  `Route::prefix('restaurant')->middleware('role:restaurant_owner,restaurant_staff')->group(function () { ... })`
  (e.g. right after the `/statements` route).

## 3. Run

```bash
php artisan migrate --force          # creates restaurant_ai_messages
php artisan optimize:clear
php artisan route:list | grep -E 'assistant|menu/analytics|restaurant/invoices|notifications/test'
```

Expect 6 routes (3 assistant + menu/analytics + invoices + notifications/test).

## 4. Requirements on the target

- Admin **AI Control Center** must have `ai_enabled = true` and an OpenAI key
  set (`AiSettingsService`). The assistant reads the key from there — nothing to
  configure in these files.
- `AiCostAwareRouter`, `AiSettingsService`, `PushNotificationService` must exist
  (they're in every current Swado backend).

---

## Endpoints / behaviour

Full reference in **`DEPLOY.md`** (endpoint list + the 11 AI data tools).

The AI assistant (`RestaurantAssistantController`) runs two OpenAI passes per
message: **PLAN** (model picks which read-only data tools to run) → **FETCH**
(queries run against that restaurant's live data, scoped + row-capped) →
**ANSWER** (model replies as JSON grounded in the figures). No hard-coded
answers, no fixed context dump.

---

## Separate hand-apply patches (edits to existing files)

| doc | what it changes |
|---|---|
| `PATCH_payouts.md` | `PayoutSettlementService`, `BulkPayoutService`, `PayoutScheduleService`, `GeneratePayouts` — auto-reverse locked wallet funds on any payout failure; separate driver vs restaurant payout days via `payout_settings.options` (no migration) |
| `PATCH_multi_restaurant.md` | `Admin\PartnerApplicationController::approveRestaurantApplication()` — reuse an existing owner login instead of always `User::create()`; + one-off merge SQL |
| `PATCH_ai_tool_registry.md` | `AiToolRegistry` / `AiNotificationImageService` — fixes `Undefined property $settings` at `AiToolRegistry.php:~458` (scheduled/queued AI notifications) |

## Non-code

- Legal `account_deletion` key — `DEPLOY.md` §D (one line in the `/content/legal` closure).
- Menu images HTTP 403 — S3 bucket `utility-ms-videos` policy only exposes
  `restaurants/logos/*`; add `menu-items/*` and `categories/*` (`DEPLOY.md` §E).
