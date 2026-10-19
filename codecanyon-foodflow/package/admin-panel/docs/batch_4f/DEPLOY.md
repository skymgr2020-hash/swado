# backend_4f — endpoint & data-tool reference

Install steps are in `README.md`. This file documents what the endpoints do.

All routes sit inside
`Route::prefix('restaurant')->middleware('role:restaurant_owner,restaurant_staff')->group(...)`
and are further gated by `restaurant.permission:` middleware (see
`routes/api.php.snippet`). Every controller resolves the target outlet the same
way as the rest of the restaurant app (owner → staff outlet →
`current_restaurant_id`, with an optional `?restaurant_id=` override) via the
`ResolvesRestaurantScope` trait.

---

## AI assistant — real, intent-driven, data-grounded (OpenAI)

| route | |
|---|---|
| `GET  /api/restaurant/assistant/status` | `{ data: { enabled, service_enabled } }` — reflects admin AI Control Center (key set + kill switch off) |
| `POST /api/restaurant/assistant/message` | body `{ message, conversation_id? }` → `{ data: { conversation_id, reply, plan:[{title,steps:[]}], suggestions:[], used_data:[] } }` |
| `GET  /api/restaurant/assistant/history?conversation_id=` | `{ data: { conversation_id, messages:[{role,text,plan}] } }` |

**Per message, `RestaurantAssistantController` runs two OpenAI passes** through
`AiCostAwareRouter::ask()` (provider forced to `openai`):

1. **PLAN** — the model reads the owner's message + last 8 turns and returns
   `{intent, fetch:[{tool,args}]}` — which read-only data tools to run.
2. **FETCH** — each requested tool runs against *this* restaurant's live data.
   Tools are whitelisted, restaurant-scoped, row-capped; the model never
   issues SQL. If planning fails, a sensible base set is fetched instead.
3. **ANSWER** — the model returns `{reply, plan, suggestions}`, told to use only
   the fetched figures, quote real numbers/dishes, cite the period, use ₹.

`used_data` in the response lists which tools actually ran (debugging aid).
Conversation turns persist to `restaurant_ai_messages`.

### Data tools the planner can call

| tool | args | returns |
|---|---|---|
| `sales_overview` | `{days=30}` | orders, revenue, AOV, restaurant earning, cancelled count, previous-period same + % change |
| `sales_by_day` | `{days=30}` | `[{date, orders, revenue}]` daily |
| `sales_by_hour` | `{days=30}` | orders by hour 0–23 and by weekday |
| `item_performance` | `{days=30, limit=20}` | per-dish `{name, qty, revenue, price, is_available, rating}`, best first |
| `item_lookup` | `{name}` | one dish + 30/60/90-day units-sold trend |
| `menu_health` | `{days=30}` | item counts, available/unavailable, price range, dishes with zero sales in period |
| `payouts_recent` | `{limit=6}` | recent cycles `{date, status, gross, platform_commission, commission_gst, gateway_fee, tds, tcs, net, failure_reason}` |
| `payout_explain` | `{id=null}` | one cycle line-by-line + order count + `breakdown` json (latest if no id) |
| `reviews_summary` | `{days=60}` | avg rating, 1–5 spread, recent ≤3-star reviews with text |
| `promotions_active` | `{}` | current `promotions` + `promo_codes` for this restaurant with usage counts |
| `cancellations` | `{days=30}` | cancelled count, cancel-rate %, top `cancellation_reason` values |

---

## Menu analytics

`GET /api/restaurant/menu/analytics?days=30`
→ `{ data: { period_days, totals:{items_sold,dishes,revenue}, items:[{id,name,qty,revenue,price,group,ai_suggestion}] } }`

`order_items` rollup for the period; dishes ranked by units, split into top /
bottom / middle thirds (`group` = `best` / `low` / `mid`). One batched OpenAI
call produces `ai_suggestion` per notable dish (skipped silently if AI is off).

---

## Invoices

`GET /api/restaurant/invoices?type=ordering|ads&months=12`
→ `{ data: { type, invoices:[{id,label,period,amount,tax,total,meta,download_url}] } }`

- `ordering` — monthly platform commission + commission-GST from settled payouts.
- `ads` — monthly ad-wallet spend from `restaurant_ad_wallet_transactions`.

`download_url` is `null` until a PDF generator exists; the app shows the figures
and a "link not available yet" note.

---

## Notification test

`POST /api/restaurant/notifications/test`
→ `{ data: { delivered, has_token, target_app:"restaurant", hint } }`

Sends a real FCM push to the signed-in device via `PushNotificationService` and
reports whether a device token is registered. The app also plays a local preview
regardless.

---

## D. Legal `account_deletion` key (edit `routes/api.php`)

The restaurant app's Legal section reads `data.account_deletion` from
`GET /api/content/legal`. Add it to that closure (~line 118):

```diff
             'refund' => $legalText('legal_refund', '...'),
+            'account_deletion' => $legalText('legal_account_deletion', 'You can request account deletion from within the app or by emailing support. We remove personal data within 30 days, except records we must retain for tax, fraud-prevention and legal-compliance purposes.'),
             'contact_email' => $contactEmail !== '' ? $contactEmail : 'support@foodflow.com',
```

Admins can override via an `AppSetting` with key `legal_account_deletion`.

## E. Menu images HTTP 403 (infra, no code)

S3 bucket `utility-ms-videos` (eu-north-1) policy only grants public read to
`restaurants/logos/*`. Add the other prefixes to the same statement's
`Resource` list:

```json
"arn:aws:s3:::utility-ms-videos/menu-items/*",
"arn:aws:s3:::utility-ms-videos/categories/*"
```
