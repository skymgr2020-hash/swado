# Swado API Contract — Native Client Reference

Single source of truth both the Android and iOS native clients transcribe DTOs
from, so two parallel codebases don't silently diverge in how they interpret
the same backend. If a backend endpoint changes, update the relevant file
here first, then update both platforms' DTOs from that diff.

Backend: `d:/karn/Swado/admin` (Laravel + Sanctum). Auth: `Authorization:
Bearer <token>` on every endpoint under the `auth:sanctum` group in
`routes/api.php`; tokens never expire server-side.

**General envelope**: most endpoints return `{success, message?, data}`, but
this is not universal — check the "known quirks" line on each entry.
**General numeric quirk**: some money fields serialize as JSON strings
(Laravel `decimal:N` casts) and others as native numbers — never assume one
or the other without checking the entry.

## Files

- [`auth.md`](auth.md) — registration, login, OTP/Firebase phone verification, branding, profile
- `restaurants.md`, `menu.md`, `cart-orders.md`, `payments.md`, `addresses.md`,
  `wallet.md`, `dining.md`, `support.md`, `realtime.md`, `push.md`, `misc.md`
  — not yet written; add one per domain as each implementation phase starts
  (see the phased plan). Don't write speculative entries ahead of the phase
  that needs them — endpoint behavior is confirmed by a real call against the
  running backend, not by reading the PHP source alone.

## How entries are captured

Each entry is verified against the **real running backend**, not just
inferred from PHP source (Eloquent casts/nullability/resource transforms can
diverge from what a static read of the controller suggests). To repeat the
capture:

```bash
cd admin
php artisan serve --port=8123
curl -s http://127.0.0.1:8123/api/<endpoint> | python3 -m json.tool
```
