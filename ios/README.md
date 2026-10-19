# Swado iOS (native clone) — Phase 0

Native SwiftUI clone of the Flutter customer app, wired to the real Laravel
backend at `d:/karn/Swado/admin`. See the full phased plan for context.

This project was written on Windows (no Xcode available there), so it has
**not** been build-verified yet. Do that first on your Mac — this phase is a
small, fast thing to check before more code is written on top of it.

## First-time setup (on your Mac)

1. Install [XcodeGen](https://github.com/yonaskolb/XcodeGen) (used instead of
   committing a hand-edited `.xcodeproj`, which is fragile to author outside
   Xcode):
   ```bash
   brew install xcodegen
   ```
2. Generate the Xcode project from `project.yml`:
   ```bash
   cd ios
   xcodegen generate
   open Swado.xcodeproj
   ```
3. Point the app at your backend: `project.yml` sets `API_BASE_URL` to
   `http://localhost:8000/api/`, which reaches your Mac's own localhost from
   the iOS Simulator directly (no `10.0.2.2` translation needed, unlike
   Android). Start the Laravel dev server first:
   ```bash
   cd ../admin && php artisan serve
   ```
   For a physical device, change `API_BASE_URL` in `project.yml` to your
   Mac's LAN IP (or a staging domain) and re-run `xcodegen generate`.
4. Build and run (⌘R) on an iOS 16+ simulator.

## What "done" looks like for Phase 0

The app launches, calls the real `GET /api/app/branding` endpoint, and
displays the real app name/subtitle from the response — no hardcoded text.
If the backend isn't reachable you'll see a "Couldn't reach the server" state
with a Retry button instead of a crash.

## Notes

- `project.yml` has commented-out package entries for PusherSwift and
  Firebase — uncomment and re-run `xcodegen generate` when Phase 5/6 need
  them, along with adding `GoogleService-Info.plist` to `Swado/Resources/`.
- The AppIcon placeholder in `Assets.xcassets` is empty; real branding-driven
  icons come in Phase 9 of the plan.
- Every time `project.yml` changes, re-run `xcodegen generate` to regenerate
  the `.xcodeproj` — don't hand-edit the generated project file.
