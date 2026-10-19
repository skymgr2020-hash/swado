# FoodFlow — CodeCanyon Submission Workspace

This folder is the staging area for the **FoodFlow** CodeCanyon submission — a resubmission of the Swado platform (previously submitted under the name "FoodPilot" and rejected; see `RESUBMISSION-NOTES.md` for the point-by-point response to that rejection).

## What's here

| Path | What it is |
|---|---|
| `build-package.ps1` | Run this to assemble and zip the actual uploadable package from the live source in `..\admin`, `..\customer`, `..\driver`, `..\restaurant`, `..\onboard`. Excludes `vendor`, `build`, `ephemeral`, `node_modules`, and any real `.env` — and verifies none of them survived before it's allowed to zip |
| `package/documentation/` | The full documentation set shipped inside the package (installation, REST API, `.env` reference, Flutter architecture, queue/workers, multi-country tax, extending the backend, troubleshooting) |
| `screenshots/` | Real screenshots captured from the running application at CodeCanyon's required dimensions (1280×720, 16:9) — admin dashboard, Branch panel (dashboard/fleet map/COD/settings), Country & Tax config, a restaurant's tax registration form, and a rendered non-India (UK VAT) invoice |
| `listing/description.md` | The CodeCanyon item description text, including the required per-service cost disclosures |
| `RESUBMISSION-NOTES.md` | Maps every point in the rejection comment to exactly what was fixed and where |

## To produce the final upload

```powershell
cd codecanyon-foodflow
.\build-package.ps1
```

This produces `FoodFlow-v1.0.0.zip` next to this README — that's the file for the "Main Files" upload. Screenshots (`screenshots/`) and the listing description (`listing/description.md`) are uploaded separately through CodeCanyon's item listing form, not inside the zip.

## What to double-check before submitting

- The `build-package.ps1` run completes without the disallowed-folder check failing (it will throw loudly if anything slipped through)
- Swap in your own `google-services.json` / `GoogleService-Info.plist` placeholders are clearly marked as placeholders in the shipped apps, not real credentials
- The Branch panel and tax-invoice screenshots in `screenshots/` were captured against development/test data (a branch named "Fleet Debug Branch" with no live orders, and a sample restaurant/order repurposed to demonstrate UK VAT) — functionally and visually accurate, but you may want to swap in screenshots from a more populated demo environment for a more polished first impression before final upload
