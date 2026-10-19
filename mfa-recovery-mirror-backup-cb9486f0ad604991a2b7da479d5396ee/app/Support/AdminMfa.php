<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class AdminMfa
{
    public static function begin(Request $request, User $user, bool $remember = false)
    {
        $request->session()->forget(['admin_mfa', 'admin_mfa_recovery_codes', 'login.id', 'login.remember']);
        $request->session()->regenerate();
        $request->session()->put('admin_mfa.pending', [
            'id' => $user->getKey(),
            'remember' => $remember,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'password_hash' => hash('sha256', $user->getAuthPassword()),
        ]);

        return $request->expectsJson()
            ? response()->json(['two_factor' => true, 'redirect' => route('admin-mfa.show')])
            : redirect()->route('admin-mfa.show');
    }

    public static function verified(Request $request, User $user): bool
    {
        return $user->hasEnabledTwoFactorAuthentication()
            && (string) $request->session()->get('admin_mfa.verified_id') === (string) $user->getKey()
            && hash_equals(hash('sha256', $user->two_factor_secret), (string) $request->session()->get('admin_mfa.secret_hash'));
    }
}
