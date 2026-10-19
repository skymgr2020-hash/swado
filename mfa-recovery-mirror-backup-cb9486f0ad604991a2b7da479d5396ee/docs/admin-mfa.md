# Admin MFA

MFA is required for web logins with the `admin` or `super_admin` role. Other roles keep their existing login behavior, including any previously enabled optional Fortify two-factor authentication. Mobile/API login endpoints are unchanged.

On the first successful password entry, the admin scans a QR code with an authenticator app and enters its current six-digit code. Enrollment is saved only after that code is verified. Eight single-use recovery codes are then shown; save them before continuing to the dashboard. Subsequent logins ask for an authenticator code or a recovery code without displaying the QR again.

Pending sign-ins expire after ten minutes. Verification permits five attempts per minute per account. Existing admin sessions and sessions restored by remember-me must also complete MFA before accessing web pages. Signing out clears the session's MFA verification.

The QR is generated locally using the installed Fortify/BaconQrCode dependencies; secrets are never sent to an external QR service. Confirmed secrets and recovery codes use Fortify's existing encrypted user columns. No new dependency or migration is required when the existing `2026_04_25_143214_add_two_factor_columns_to_users_table` migration has been applied. Keep the application's encryption key stable.

After deploying the changed PHP and Blade files, clear cached routes/config/views with `php artisan optimize:clear` through the normal deployment process. Existing admins will be prompted to enroll or verify on their next web request. This change does not deploy itself or alter the live database.

Run the isolated feature suite with:

```sh
php vendor/bin/phpunit tests/Feature/AdminMfaTest.php
```

The suite uses an in-memory SQLite database and array cache/session, without loading `.env` deployment credentials.
