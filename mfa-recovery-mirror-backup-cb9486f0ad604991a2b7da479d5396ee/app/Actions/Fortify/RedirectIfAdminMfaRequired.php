<?php

namespace App\Actions\Fortify;

use App\Support\AdminMfa;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

class RedirectIfAdminMfaRequired extends RedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        // Never expose enrollment credentials before validating the password.
        $user = $this->validateCredentials($request);
        $request->session()->forget(['admin_mfa', 'admin_mfa_recovery_codes']);

        if ($user->requiresAdminMfa()) {
            return AdminMfa::begin($request, $user, $request->boolean('remember'));
        }

        return parent::handle($request, $next);
    }
}
