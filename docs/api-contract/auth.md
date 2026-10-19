# Auth & Branding

Source: `admin/app/Http/Controllers/Api/AuthController.php`. Guard:
`auth:sanctum`, Bearer token, never expires (`config/sanctum.php
expiration=null`). No refresh-token flow — a 401 means force-logout.

## `GET /api/app/branding` — public

Used at app startup, before login, to drive splash/theme/onboarding content
and to pick the phone-verification path (`otp_service_provider`). Real
response captured 2026-08-17 against the local dev DB (values are
admin-configurable per deployment — treat the *shape*, not these specific
values, as the contract):

```json
{
  "success": true,
  "data": {
    "app_name": "SwaadSathi",
    "app_logo": null,
    "app_icon": null,
    "app_favicon": null,
    "frontend_background_image": null,
    "primary_color": "#FF5A1F",
    "secondary_color": "#2B2A33",
    "restaurant_primary_color": "#FF5A1F",
    "restaurant_secondary_color": "#2B2A33",
    "driver_primary_color": "#FF5A1F",
    "driver_secondary_color": "#2B2A33",
    "customer_play_store_url": "",
    "customer_deeplink_scheme": "foodflow",
    "customer_deeplink_base_url": "",
    "customer_order_deeplink_template": "foodflow://orders/{order_id}",
    "customer_restaurant_deeplink_template": "foodflow://restaurants/{restaurant_id}",
    "customer_wallet_deeplink_template": "foodflow://wallet",
    "vendor_mode": "multi",
    "single_restaurant_id": null,
    "single_restaurant_name": null,
    "app_releases": {
      "customer": { "latest_version": "", "latest_build_number": 0, "min_supported_build_number": 0, "force_update": false, "android_update_url": "", "ios_update_url": "", "release_notes": "" },
      "restaurant": { "...": "same shape" },
      "driver": { "...": "same shape" }
    },
    "support_email": "support@example.com",
    "support_phone": "",
    "default_mobile_country_code": "+91",
    "otp_service_provider": "",
    "cod_enabled": true,
    "social_login": {
      "enabled": false,
      "auto_register": true,
      "auto_link_verified_email": true,
      "providers": {
        "google": { "enabled": false, "web_client_id": "" },
        "apple": { "enabled": false, "services_id": "" }
      }
    },
    "pusher_app_key": "8c6ea31481849be0004d",
    "pusher_app_cluster": "ap2",
    "header_branding_type": "text",
    "onboarding_intro_title": "SwaadSathi",
    "onboarding_intro_subtitle": "Food, groceries and everyday cravings delivered fast.",
    "onboarding_slides": [
      { "title": "...", "description": "...", "image": "https://..." }
    ]
  }
}
```

**Known quirks:**
- `otp_service_provider` can be an **empty string**, not just `firebase|twilio|msg91` — treat empty/unrecognized as "use the custom OTP flow" (the default), don't crash on an unexpected enum value.
- `single_restaurant_id`/`single_restaurant_name` are non-null only when `vendor_mode = "single"` (single-restaurant white-label deployment) — customer app should branch discovery UX on this if supporting both modes.
- Color fields can be `null` — always fall back to the client's default palette, never force-unwrap.

## `POST /api/register` — public, requires prior phone verification

Request: `name`, `email` (required unless role=customer), `phone`,
`verified_phone_token` (required, min 20 chars — obtained from
`verifyOtp`/`verifyFirebasePhone` with `flow=signup`), `role` (optional,
default `customer`), `referral_code` (optional).

Response 201:
```json
{ "success": true, "message": "Registration successful",
  "data": { "user": { "id": 1, "name": "...", "email": "...", "phone": "...", "roles": [{ "id": 6, "name": "customer", "guard_name": "web", "created_at": "...", "updated_at": "...", "pivot": {...}, "permissions": [{...}] }] }, "token": "1|xxxxx..." } }
```
**Known quirk (corrected after a real capture — this was wrongly documented as `roles: ["customer"]` before):** `roles` is an array of full Spatie role **objects** (id/name/guard_name/pivot/nested permission objects), not plain strings. This crashed both native clients' JSON decoders when first captured live (`List<String>` in Kotlin, `[String]` in Swift) — both now simply omit a `roles` property entirely, since the separate top-level `role` string field (see `buildUserPayload()` below) already covers everything the apps need.

**Known quirk:** registration is impossible without first completing a phone-verification call — enforce that sequencing in the UI (OTP screen before the registration form submits), not just a validation error after the fact.

## `POST /api/login` — public

Request: `email` (required), `password` (required), `role` (optional).
Response 200: same `{user, token}` shape as register. 401 on bad credentials, 403 on inactive/role mismatch.

**Known quirk:** this is email+password only. There is no phone+password login endpoint — phone-based login goes through `POST /auth/login-with-phone` (Firebase ID token) or the OTP flow below.

## Phone verification — two parallel mechanisms, chosen by `otp_service_provider`

**Custom OTP** (`otp_service_provider` = `""`, `twilio`, or `msg91`):
- `GET|POST /api/auth/otp/send` — body `{phone, flow: login|signup|forgot_password, role?}` → sends a 6-digit code, response includes `existing_user`, `provider`, `expires_in`.
- `POST /api/auth/otp/verify` — body `{phone, otp, flow, role?}` → for `flow=login`, returns `{user, token, phone}` (issues token); for `flow=signup`/`forgot_password`, returns `{verified_phone_token, requires_registration|requires_password_reset}` (no token — feed into `/register` or `/forgot-password/reset-by-phone`).

**Firebase Phone Auth** (`otp_service_provider` = `firebase`):
- Client runs Firebase Phone Auth SDK locally to get a `firebase_id_token`.
- `POST /api/auth/login-with-phone` — body `{phone, firebase_id_token, role?}` → `{user, token, phone, firebase_uid}` (issues token directly, this IS the login call).
- `POST /api/auth/phone/verify-firebase` — body `{phone, firebase_id_token, flow: signup|forgot_password, role?}` → `{verified_phone_token, requires_registration|requires_password_reset, firebase_uid}` (no token — same downstream use as the custom-OTP signup/forgot-password branch).

**Known quirk:** both mechanisms exist simultaneously in the backend; the client must branch on `otp_service_provider` from `/app/branding`, not hardcode one path.

## `POST /api/auth/social-login` — public

Request: `provider` (`google`|`apple`), `firebase_id_token` (Firebase ID token from native Google/Apple sign-in run through Firebase Auth SDK), `role?`, `display_name?`, `referral_code?`.
Response 200: `{user, token, provider}`. 409 `SOCIAL_LINK_DISABLED`, 404 `ACCOUNT_NOT_FOUND`, 403 `SOCIAL_SIGNUP_ROLE_NOT_ALLOWED` for edge cases — surface these as distinct UI states, not a generic error toast.

## `GET /api/user` — auth required

Returns the full user payload, `buildUserPayload(includeFullUser=true)`:
```json
{
  "success": true,
  "data": {
    "id": 1, "name": "...", "email": "...", "phone": "...",
    "role": "customer", "profile_image": null, "profile_photo_url": null,
    "current_restaurant_id": null, "branch_id": null,
    "referral_code": "SWDABC1234", "referred_by_user_id": null,
    "reward_points_balance": 0, "branch": null, "permissions": [],
    "settings": {
      "currency_code": "INR", "currency_symbol": "₹", "currency_decimals": 2,
      "payment_gateway_provider": "razorpay",
      "enabled_payment_gateways": ["razorpay"],
      "payment_gateways": [{ "key": "razorpay", "label": "Razorpay", "logo": null, "enabled": true, "selected": true }],
      "cod_enabled": true, "otp_service_provider": "",
      "default_mobile_country_code": "+91",
      "social_login": { "...": "same shape as branding.social_login" }
    }
  }
}
```

**Known quirk:** `settings.enabled_payment_gateways` is the authoritative list for which payment SDKs the checkout screen should offer — don't show a gateway just because the app has the SDK integrated.

## Other auth-adjacent endpoints (auth required unless noted)

- `POST /api/logout` — deletes only the current token (`currentAccessToken()->delete()`), not all sessions.
- `POST /api/user/fcm-token` — body `{fcm_token, target_app: "customer", role?}`.
- `PUT|POST /api/user/profile`, `POST /api/user/change-password`, `DELETE /api/user/account` (soft-anonymizes, not a hard delete).
- `POST /api/forgot-password` (public) — phone path delegates to `otp/send` with `flow=forgot_password`; email path uses Laravel's email reset link (not really used by native clients — prefer the phone path).
- `POST /api/forgot-password/reset-by-phone` (public) — body `{phone, verified_phone_token, password, password_confirmation, role?}`, no token returned — client must log in afterward.
