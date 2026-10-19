# Restaurant Discovery

Source: `admin/app/Http/Controllers/Api/RestaurantController.php`, all public
(no auth). Real response captured 2026-08-17 against `swado.online` — the
production instance has exactly two seeded restaurants, both in Adityapur,
Jamshedpur (lat ~22.79, lng ~86.16); coordinates elsewhere (e.g. Bangalore)
return an empty `data: []`, which is a legitimate state to test, not a bug.

## `GET /api/restaurants/nearby?lat&lng&radius&query&open_now`

```json
{
  "success": true,
  "data": [
    {
      "id": 1, "name": "Sri Krishna Thali", "slug": "sri-krishna-thali",
      "description": null, "email": "...", "phone": "9204885453", "fssai_license_number": null,
      "address": "2, Rd Number 32, Raydih, Adityapur, Jamshedpur, Jharkhand 831013, India",
      "city": "Jamshedpur", "state": "Jharkhand", "pincode": "831013",
      "latitude": 22.7901, "longitude": 86.1657,
      "cuisine": ["North Indian", "Chinese", "Continental"],
      "cuisine_ids": [1, 3, 5], "cuisine_text": "North Indian, Chinese, Continental",
      "is_open": true, "manual_is_open": true, "is_open_now": true,
      "next_opening_time": "2026-08-18T00:00:00+05:30", "next_opening_label": "Opens tomorrow at 12:00 AM",
      "is_pure_veg": true,
      "min_order_amount": 0, "amount_for_one": 95,
      "delivery_fee": 40, "delivery_time": 5, "restaurant_type": "all",
      "accepts_delivery": true, "accepts_dining": true, "accepts_takeaway": true,
      "dining_charge": "0.00",
      "rating": null, "total_ratings": 1,
      "banner_image": "https://swado.online/storage/restaurants/banners/....png",
      "logo_image": "https://swado.online/storage/restaurants/logos/....png",
      "distance": 0.4978689253786452,
      "matched_item_names": [], "matched_menu_items": [],
      "weekly_timings": { "monday": { "is_open": true, "open_time": "00:00", "close_time": "23:59", "break_start": null, "break_end": null }, "...": "one entry per day" },
      "is_featured": true, "orders_count": 11, "created_at": "2026-08-07T16:20:54+00:00",
      "eta_minutes": 5, "eta_range": "5-10 mins", "travel_minutes": 5, "travel_distance_km": 0.5,
      "preparation_minutes": 0, "eta_source": "haversine_estimate"
    }
  ],
  "meta": { "page": 1, "per_page": 0, "total": 0, "has_more": false, "next_page": null }
}
```

**Known quirks:**
- `dining_charge` is a **string** (`"0.00"`), not a number — Laravel `decimal` cast serializing as string, same family of quirk as `MenuItemResource.price`. Not currently consumed by the native clients' `RestaurantDto`; parse as a string if it's ever needed.
- `rating` is frequently `null` (not gated strictly by a ratings-count threshold in practice — seen `null` with `total_ratings: 1`). Always null-check before formatting.
- `min_order_amount`/`amount_for_one` can be plain integers or `null` — both native `RestaurantDto`s declare them as nullable `Double`/`Double?`, which decodes an integer JSON value fine.
- `meta.total` was seen as `0` even when `data` had entries — don't rely on `meta` for the item count, use `data.length`.
- Empty-radius/no-nearby-restaurants is a normal 200 response (`{success:true, data:[]}`), not an error — render an empty state, not a failure state.

## Native DTOs

- Android: `RestaurantDto` (`android/.../data/network/dto/RestaurantDto.kt`)
- iOS: not yet ported (Phase 2 built Android-first this pass)

Both should stay a **subset** of the real fields above — extend only when a screen actually needs a new field, transcribing the exact key from this doc rather than guessing.
