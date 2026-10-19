# Patch — multi-restaurant owner reuse on application approval

## The bug

`Admin\PartnerApplicationController::approveRestaurantApplication()` runs
`User::create([...])` **unconditionally** for every approved restaurant
application. When an existing owner applies to add a second outlet (same
`contact_email` / `contact_phone`), approval creates a **brand-new login** and
points the new `Restaurant.owner_id` at it. Result on prod:

| restaurant | owner_id | owner name | phone / email |
|---|---|---|---|
| 1 | 8 | karn Goswami | (dup) |
| 2 | 9 | karn Goswami | (dup) |

So `GET /api/restaurant/restaurants` (`$user->restaurants()` = `hasMany(Restaurant, 'owner_id')`)
only ever returns the one outlet tied to whichever login the owner used — the
multi-outlet switcher can never see both.

## Fix — reuse the existing owner

`app/Http/Controllers/Admin/PartnerApplicationController.php`, in
`approveRestaurantApplication()`:

```diff
     protected function approveRestaurantApplication(PartnerApplication $application): Restaurant
     {
         $bankDetails = json_decode($application->bank_details ?? '[]', true);
         $bankDetails = is_array($bankDetails) ? $bankDetails : [];
         $meta = is_array($application->onboarding_meta) ? $application->onboarding_meta : [];

-        $owner = User::create([
-            'name' => $application->contact_name,
-            'email' => $application->contact_email,
-            'phone' => $application->contact_phone,
-            'password' => $application->password,
-            'is_active' => true,
-            'account_holder_name' => $bankDetails['holder_name'] ?? null,
-            'bank_name' => $bankDetails['bank_name'] ?? null,
-            'account_number' => $bankDetails['account_number'] ?? null,
-            'ifsc_code' => $bankDetails['ifsc'] ?? null,
-            'upi_id' => $bankDetails['upi_id'] ?? null,
-        ]);
-        $owner->assignRole('restaurant_owner');
+        // Reuse an existing login if this person already partners with us
+        // (adding a 2nd/3rd outlet) — matched on phone first, then email.
+        $owner = User::query()
+            ->when($application->contact_phone, fn ($q) => $q->orWhere('phone', $application->contact_phone))
+            ->when($application->contact_email, fn ($q) => $q->orWhere('email', $application->contact_email))
+            ->first();
+
+        if ($owner) {
+            // Only backfill blanks — never stomp the owner's existing bank details.
+            $owner->fill(array_filter([
+                'name' => $owner->name ?: $application->contact_name,
+                'account_holder_name' => $owner->account_holder_name ?: ($bankDetails['holder_name'] ?? null),
+                'bank_name' => $owner->bank_name ?: ($bankDetails['bank_name'] ?? null),
+                'account_number' => $owner->account_number ?: ($bankDetails['account_number'] ?? null),
+                'ifsc_code' => $owner->ifsc_code ?: ($bankDetails['ifsc'] ?? null),
+                'upi_id' => $owner->upi_id ?: ($bankDetails['upi_id'] ?? null),
+                'is_active' => true,
+            ], fn ($v) => $v !== null && $v !== ''));
+            $owner->save();
+        } else {
+            $owner = User::create([
+                'name' => $application->contact_name,
+                'email' => $application->contact_email,
+                'phone' => $application->contact_phone,
+                'password' => $application->password,
+                'is_active' => true,
+                'account_holder_name' => $bankDetails['holder_name'] ?? null,
+                'bank_name' => $bankDetails['bank_name'] ?? null,
+                'account_number' => $bankDetails['account_number'] ?? null,
+                'ifsc_code' => $bankDetails['ifsc'] ?? null,
+                'upi_id' => $bankDetails['upi_id'] ?? null,
+            ]);
+        }
+
+        if (! $owner->hasRole('restaurant_owner')) {
+            $owner->assignRole('restaurant_owner');
+        }
```

Everything after (`Restaurant::create(['owner_id' => $owner->id, ...])`,
`$owner->update(['current_restaurant_id' => $restaurant->id])`) stays as-is — the
new outlet now hangs off the existing login, so `myRestaurants` returns all of
them and the app's outlet switcher works.

> Optional hardening: also block the register wizard (`Api\PartnerApplicationController@submit`)
> from being used to change an *already-registered* owner's name — or just accept
> that the first approval's `contact_name` wins, as above.

## One-off prod data repair

After deploying the patch, merge the duplicate that already exists. Pick the
login the owner actually uses (the one with a real device token / recent
`last_login_at`); assume that's **user 8** here:

```sql
-- 1. move outlet 2 to the surviving owner
UPDATE restaurants SET owner_id = 8 WHERE id = 2;

-- 2. move any staff / wallet / payout rows off user 9
UPDATE restaurant_staff        SET user_id = 8 WHERE user_id = 9;
UPDATE wallets                 SET user_id = 8 WHERE user_id = 9
  AND NOT EXISTS (SELECT 1 FROM wallets w2 WHERE w2.user_id = 8);   -- else transfer balance manually
UPDATE payouts                SET restaurant_id = 2 WHERE restaurant_id = 2;  -- unchanged, just checking scope

-- 3. neutralise the orphan login so it can't be used
UPDATE users SET is_active = 0, phone = CONCAT(phone, '_merged_9'), email = CONCAT('merged9_', email)
WHERE id = 9;
```

Verify: `SELECT id, owner_id, name FROM restaurants WHERE owner_id = 8;` should
list both outlets, and logging in as user 8 → `GET /api/restaurant/restaurants`
returns 2 rows.
