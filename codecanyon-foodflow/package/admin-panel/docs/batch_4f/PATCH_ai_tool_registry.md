# Patch — `AiToolRegistry::$settings` undefined (admin AI notifications)

## Error

```
production.ERROR: Undefined property: App\Services\Ai\AiToolRegistry::$settings
  at app/Services/Ai/AiToolRegistry.php:458
```

Pre-existing bug, unrelated to batch 4f. `AiToolRegistry::sendNotification()` at
line ~458 calls `$this->settings->bool('ai_image_async', false)`, but the class
has **no `$settings` property** — the constructor never receives an
`AiSettingsService`. It only fires from a console/queue context
(`app()->runningInConsole()` branch), so scheduled / queued AI notifications hit
it while chat-triggered ones never did.

## Fix — put the flag on the image service (where the sibling flags already live)

**`app/Services/Ai/AiNotificationImageService.php`** — add next to `imagesEnabled()` (~line 102):

```php
    public function imagesEnabled(): bool
    {
        return $this->settings->bool('ai_notification_images_enabled', true);
    }

+    /** Owner opted into async artwork generation (queue/CLI hands off instead of inline). */
+    public function asyncEnabled(): bool
+    {
+        return $this->settings->bool('ai_image_async', false);
+    }
+
```

**`app/Services/Ai/AiToolRegistry.php`** line ~458:

```diff
-            } elseif (app()->runningInConsole() && ! $this->settings->bool('ai_image_async', false)) {
+            } elseif (app()->runningInConsole() && ! $this->notificationImages->asyncEnabled()) {
```

`$this->notificationImages` is already an injected constructor dependency, so no
other change is needed.

## Alternative (one file)

If you'd rather not touch `AiNotificationImageService`, inject the service
straight into `AiToolRegistry` — same namespace, no `use` line required:

```diff
         private readonly PushNotificationService $pushNotifications,
         private readonly AiNotificationImageService $notificationImages,
+        private readonly AiSettingsService $settings,
     ) {
     }
```

## After applying

```bash
php artisan optimize:clear
# re-run whatever hit it, e.g.
php artisan ai:flush-stale-notifications   # (name may differ on your build)
```
