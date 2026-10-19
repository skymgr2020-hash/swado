# Backend spec — restaurant retain-customer flow + ad delete + promo phone targeting

All routes are added under `Route::middleware('auth:sanctum')->prefix('restaurant')` and
resolved for the authenticated restaurant owner (same `getAuthenticatedRestaurant($user)`
pattern already used by `RestaurantController`).

---

## 1. Complaint reply  — `POST /api/restaurant/complaints/{id}/reply`

Body: `{ "message": "string (1..1000)" }`

- Load the complaint scoped to `restaurant_id`; 404 otherwise.
- Append a reply row (new table `complaint_replies` OR a `restaurant_reply` text column +
  `restaurant_replied_at` on the complaints table — pick whichever matches the admin side).
- Push a notification to the complaint's customer:
  `type = "complaint_reply"`, `target_app = "customer"`, deep-link to that complaint/order.
- Response: `{ success, data: { complaint: <formatted> } }`

`GET /api/restaurant/complaints` should also start returning `replies` (or
`restaurant_reply` + `restaurant_replied_at`) so the app can show the thread.

---

## 2. Review reply  — `POST /api/restaurant/reviews/{id}/reply`

Body: `{ "message": "string (1..500)" }`

- Load review scoped to `restaurant_id`; 404 otherwise.
- Store `restaurant_reply` + `restaurant_replied_at` on the review row.
- Optional notification to the reviewer (`type = "review_reply"`).
- Response includes the updated review.

The app sends a **preset thank-you message** for 5★ (one tap) and a free-text reply for
< 3★. Same endpoint for both.

`GET /api/restaurant/reviews` returns `restaurant_reply` + `restaurant_replied_at`.

---

## 3. Restaurant-funded retain coupon  — `POST /api/restaurant/retain/coupon`

Issued from a complaint OR a low review as a "come back" offer.

Body:
```json
{
  "source": "complaint" | "review",
  "source_id": 123,                 // complaint id or review id
  "customer_phone": "9876543210",   // OR "customer_id": 45
  "order_id": 789,                  // the order the complaint/review is about
  "percent_of_order": 25 | 50 | 75 | 100,   // suggestion chips
  "expires_in_days": 30             // default 30
}
```

Rules:
- `value` = `round(order.total * percent_of_order / 100)`.
- **Minimum**: `percent_of_order` must be **>= 25** (i.e. min coupon value is 25% of the
  triggering order value). Reject 422 if lower.
- Coupon is:
  - **single use**, `per_user_limit = 1`, `usage_limit = 1`
  - **scoped to this restaurant only** (`restaurant_id` set; not platform-wide)
  - **scoped to this customer only** (`assigned_to = <resolved user id>`, `coupon_type = "prepaid"`)
  - **funded by the restaurant** — set `funding_source = "restaurant"` (new column on the
    promotions/coupons table) so settlement deducts the redeemed amount from the
    restaurant's payout, NOT the platform. Reuse the existing promo/coupon model
    (`promotion_type = "flat_discount"`, `reward_type = "flat"`, `discount_value = value`,
    `application_mode = "coupon"`, `min_order_amount` = e.g. `value` or `value * 1.2`).
  - `valid_from = now`, `valid_to = now + expires_in_days`.
- Generate a human code, e.g. `SORRY<value>` / `THANKS<value>` + random suffix (ensure unique).
- **Notify the customer**: push notification `type = "retain_coupon"`, `target_app =
  "customer"`, title "A gift from <restaurant name>", body "Use code <CODE> for
  <formatCurrency(value)> off your next order at <restaurant name>", data carries the code +
  restaurant_id so the customer app can deep-link to that restaurant with the coupon
  pre-applied.
- Also record on the source complaint/review (`retain_coupon_code`, `retain_coupon_id`) so
  the restaurant app can show "Coupon CODE sent" instead of the button once issued.
- Response: `{ success, data: { code, value, expires_at } }`

Redemption: the existing `checkout/coupon/apply` / `api/promotions/coupon/validate` path
already handles `assigned_to` + `restaurant_id` scoping. Add a guard: if
`funding_source == "restaurant"`, the discount is booked against the restaurant in
settlement (mirror how restaurant-funded promos are already settled, if that exists;
otherwise this is the one new settlement rule).

---

## 4. Ad campaign delete  — `DELETE /api/restaurant/ads/campaigns/{id}`

- Load campaign scoped to `restaurant_id`; 404 otherwise.
- Allow delete when `status in ('draft','rejected','paused','budget_exhausted','completed')`.
  For `status = 'active'` either 422 ("pause first") or auto-pause then soft-delete.
- Soft-delete (keep spend history for billing). Response `{ success, message }`.

The app already calls `_api.delete(ApiConstants.restaurantAdCampaign(id))` from the campaign
performance screen's ⋮ menu and pops on success.

---

## 5. Promo targeting by phone  — `createPromo` / `updatePromo` (`RestaurantController`)

The app now sends `assigned_to_phone` (string) instead of `assigned_to` (int) for the
"specific user" coupon step.

In both `createPromo` and `updatePromo` validation/handling:
```php
'assigned_to'       => 'nullable|integer|exists:users,id',
'assigned_to_phone' => 'nullable|string|max:20',
```
Before saving, if `assigned_to` is null and `assigned_to_phone` is present:
```php
$normalized = $this->normalizePhone($validated['assigned_to_phone']); // reuse AuthController helper
$user = User::where('phone', $normalized)->first();
if (!$user) return response()->json(['success'=>false,'message'=>'No customer found with that mobile number.'], 422);
$validated['assigned_to'] = $user->id;
```
Return `assigned_to_phone` in the promo payload (`GET /restaurant/promos/{id}`) so the
editor can pre-fill it on edit.
