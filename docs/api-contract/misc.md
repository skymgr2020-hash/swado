# Home Section Management, Content & Misc

## `GET /api/home/sections?lat&lng&radius` — public

Source: `admin/app/Services/HomeSectionService.php` via `ContentController::homeSections`.
This is the backend's **Home Section Management** — an admin-ordered list of
section "slots" for the home feed. Real capture 2026-08-17 against
`swado.online` at the seeded restaurants' location (lat 22.79, lng 86.16):

```json
{
  "success": true,
  "data": [
    { "token": "categories", "type": "categories", "title": "Explore Categories", "subtitle": "Discover food by cuisines & categories",
      "items": [ { "id": 1, "type": "cuisine", "cuisine_id": 1, "cuisine_ids": [1], "cuisines": [{"id":1,"name":"North Indian"}], "category_id": null, "name": "North Indian", "slug": null, "description": null, "icon": "fas fa-utensils", "image": null, "image_url": null }, "...9 more" ] },

    { "token": "deals_for_you", "type": "deals_for_you", "title": "Deals for You", "subtitle": "All active offers and promotions in one place", "strict_items": true,
      "items": [ { "id": 1, "title": "20% off on First Order", "description": null, "promotion_type": "percentage_discount", "discount_type": "percentage", "discount_value": 20, "image_url": null, "restaurant_id": 1, "targets": {"...": "..."}, "conditions": {"...": "..."}, "...": "full Promotion model, many more fields" } ] },

    { "token": "restaurant_discovery", "type": "restaurant_discovery", "title": "Restaurants Near You", "subtitle": "Discover the best restaurants in your area",
      "items": [] },

    { "token": "menu_price_filter_config", "type": "menu_price_filter_config", "title": "Filter", "style": "nav_action", "strict_items": true,
      "items": [ { "id": "menu_price_filter", "title": "Filter", "label": "Filter", "screen_title": "Items in your budget", "screen_subtitle": "Menu items matched from restaurants near you", "min_price": null, "max_price": 250 } ] }
  ]
}
```

**Known quirk (important, easy to misread as a bug):** `restaurant_discovery` sections **always** have `items: []` — `HomeSectionService.php:270-278` hardcodes `'items' => collect()` for this type. This is not empty-because-no-restaurants; it's a deliberate marker telling the client "render the restaurant grid here." The client must separately call `GET /restaurants/nearby` (see `restaurants.md`) with the same lat/lng and splice the results into this section's position — never wait for or retry this section expecting it to populate.

**Other quirks:**
- `icon` on `categories` items is a **FontAwesome class string** (e.g. `"fas fa-utensils"`), not a bundled asset — the Flutter app presumably renders these via a FontAwesome font package; native clients without that dependency should map cuisine name → a local icon/emoji instead of trying to parse the FA class.
- `deals_for_you` items are **full `Promotion` model objects** (id, targets, conditions, funding fields, etc. — see `Promotion` model in the auth.md/restaurants.md model notes) — only render the subset relevant to a promo card (`title`, `description`, `discount_type`, `discount_value`, `image_url`); don't attempt to model every field.
- Section list, order, and even which tokens exist are **entirely admin-configurable** — don't hardcode an assumption that exactly these 4 tokens/types always exist. Render generically by `type`, and skip any `type` the client doesn't have a renderer for yet (forward-compatible, matches what both native `HomeTabScreen`s do).
- Passing no `lat`/`lng` still returns 200 (categories/deals/filter sections are location-independent; `restaurant_discovery` is always empty regardless).

## Native DTOs

- Android: `HomeSectionDto`, `CuisineItemDto`, `DealItemDto` (`android/.../data/network/dto/HomeSectionDto.kt`) — `items` stays raw `JsonElement`, decoded per-type on demand rather than one rigid shape, since item shape varies by section `type`.
- iOS: not yet ported.
