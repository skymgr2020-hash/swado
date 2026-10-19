# FoodFlow — Multi-Vendor Food Delivery Platform

FoodFlow is a complete, self-hosted food delivery platform: a Laravel admin panel and four native Flutter apps (Customer, Restaurant Partner, Driver, and an internal Onboarding tool), with an optional franchise/multi-branch layer and multi-country tax support built in from day one.

## What's included

- **Admin Panel** (Laravel) — orders, restaurants, drivers, users, menu catalog, payouts, promotions, reports, role-based permissions, and a full Branch/franchise management layer
- **Customer App** (Flutter, Android + iOS) — browse, order, track, pay, rate
- **Restaurant Partner App** (Flutter) — accept/manage orders, manage menu, view earnings
- **Driver App** (Flutter) — accept deliveries, navigate, collect cash, track earnings
- **Branch Panel** — for multi-location/franchise operators: a scoped dashboard, fleet map, COD reconciliation, and settings per branch, separate from the platform-wide admin view
- **Multi-country tax engine** — India GST out of the box, plus UK, GCC (UAE/Saudi/Bahrain/Oman), EU (Germany/France/Spain/Italy/Netherlands/Ireland), US, Australia, and Singapore — 15 countries total, each with correct tax terminology and invoice format, not a one-size-fits-all GST label stretched over every region
- **Role & permission system** — every admin function (orders, restaurants, payouts, settings, etc.) is individually grantable per role, including a dedicated Support Desk role scoped to ticket handling only
- **Full documentation set** — installation guide, REST API reference, environment variable reference, Flutter app architecture guide, queue/worker setup guide, multi-country tax guide, backend extension guide, and platform-specific troubleshooting (see the `documentation/` folder)

## Important: third-party service costs (not included in this purchase)

FoodFlow integrates with several third-party services for AI, messaging, maps, and payments. **These are separate, optional-where-noted services with their own billing — none of their costs are included in the price of this item, and you will need your own account with each one you choose to enable.**

| Service | What it's used for | How it's billed | Required? |
|---|---|---|---|
| **OpenAI** | Optional AI assistant / autonomous operations features in the admin panel | Pay-as-you-go, billed per token by OpenAI | Optional |
| **Google Gemini** | Alternate AI provider for the same assistant features | Pay-as-you-go, billed per token by Google | Optional |
| **Twilio** | SMS OTP delivery, voice calling | Billed per SMS sent and per call minute by Twilio | Optional (one of several SMS/voice provider choices) |
| **MSG91** | SMS OTP delivery (India-focused) | Billed per SMS by MSG91 | Optional (one of several SMS provider choices) |
| **Exotel** | Masked/anonymous calling between customer and driver/restaurant | Billed per masked call minute by Exotel | Optional |
| **Google Maps Platform** | Address autocomplete, geocoding, distance/ETA calculation, map display | Free tier, then usage-based billing by Google beyond it | **Required** — the platform needs a map provider |
| **Razorpay** | Payment collection and driver/restaurant payouts (India) | Per-transaction fee charged by Razorpay | One payment gateway required |
| **Stripe** | Payment collection and payouts (international) | Per-transaction fee charged by Stripe | One payment gateway required |
| **Cashfree** | Payment collection, payouts, and ID verification (PAN/GSTIN/driving licence/Aadhaar) during partner onboarding | Per-transaction fee charged by Cashfree | One payment gateway required |

You need **at least one payment gateway** (Razorpay, Stripe, or Cashfree) and **Google Maps Platform** to run a functioning deployment. AI, SMS/OTP, and masked calling can each be left disabled; the relevant admin features simply won't be available until you configure a provider for them.

## Requirements

- PHP 8.2+, MySQL 8+, Composer 2
- Flutter 3.x toolchain to build the mobile apps
- A supervised background worker process and a system cron entry (see the Queue & Workers doc — this is required for driver assignment, scheduled payouts, and interval delivery to function)

## Support

Documentation covers installation, API integration, and platform-specific troubleshooting in depth — see the `documentation/` folder included with your purchase.
