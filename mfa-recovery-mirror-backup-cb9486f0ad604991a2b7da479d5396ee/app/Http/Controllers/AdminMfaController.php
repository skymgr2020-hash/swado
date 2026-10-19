<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminMfa;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

class AdminMfaController extends Controller
{
    public function show(Request $request, TwoFactorAuthenticationProvider $provider)
    {
        $user = $this->pendingUser($request);
        $setup = ! $user->hasEnabledTwoFactorAuthentication();
        $qrCode = $secret = null;

        if ($setup) {
            // Keep unconfirmed enrollment in this session, never overwrite another session's setup.
            if (! $request->session()->has('admin_mfa.setup_secret')) {
                $request->session()->put('admin_mfa.setup_secret', Fortify::currentEncrypter()->encrypt($provider->generateSecretKey(32)));
            }

            $user->two_factor_secret = $request->session()->get('admin_mfa.setup_secret');
            $qrCode = $user->twoFactorQrCodeSvg();
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        }

        return response()->view('auth.admin-mfa', compact('setup', 'qrCode', 'secret'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function verify(Request $request, TwoFactorAuthenticationProvider $provider)
    {
        $pendingUser = $this->pendingUser($request);
        $request->validate([
            'code' => ['nullable', 'required_without:recovery_code', 'regex:/^[0-9]{6}$/'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:100'],
        ]);

        [$user, $recoveryCodes] = DB::transaction(function () use ($request, $provider, $pendingUser) {
            $user = User::whereKey($pendingUser->getKey())->lockForUpdate()->firstOrFail();
            $setup = ! $user->hasEnabledTwoFactorAuthentication();
            $secret = $setup ? $request->session()->get('admin_mfa.setup_secret') : $user->two_factor_secret;
            $valid = false;

            if (! $setup && $request->filled('recovery_code')) {
                foreach ($user->recoveryCodes() as $recoveryCode) {
                    if (hash_equals($recoveryCode, (string) $request->input('recovery_code'))) {
                        $user->replaceRecoveryCode($recoveryCode);
                        $valid = true;
                        break;
                    }
                }
            } elseif ($secret && $request->filled('code')) {
                $valid = $provider->verify(Fortify::currentEncrypter()->decrypt($secret), $request->input('code'));
            }

            if (! $valid) {
                throw ValidationException::withMessages(['code' => __('The authentication code is invalid or has expired.')]);
            }

            $recoveryCodes = [];
            if ($setup) {
                $recoveryCodes = collect(range(1, 8))->map(fn () => RecoveryCode::generate())->all();
                $user->forceFill([
                    'two_factor_secret' => $secret,
                    'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($recoveryCodes)),
                    'two_factor_confirmed_at' => now(),
                ])->save();
            }

            return [$user, $recoveryCodes];
        });

        $remember = (bool) $request->session()->get('admin_mfa.pending.remember');
        $request->session()->forget('admin_mfa');
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put([
            'admin_mfa.verified_id' => $user->getKey(),
            'admin_mfa.secret_hash' => hash('sha256', $user->two_factor_secret),
            'auth.password_confirmed_at' => time(),
        ]);

        if ($recoveryCodes) {
            $request->session()->flash('admin_mfa_recovery_codes', $recoveryCodes);

            return redirect()->route('admin-mfa.recovery');
        }

        return redirect()->route('admin.dashboard');
    }

    public function recovery(Request $request)
    {
        abort_unless($request->user()->requiresAdminMfa() && AdminMfa::verified($request, $request->user()), 403);
        $recoveryCodes = $request->session()->get('admin_mfa_recovery_codes');

        if (! $recoveryCodes) {
            return redirect()->route('admin.dashboard');
        }

        return response()->view('auth.admin-mfa', ['recoveryCodes' => $recoveryCodes])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function cancel(Request $request)
    {
        $request->session()->forget(['admin_mfa.pending', 'admin_mfa.setup_secret']);
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function pendingUser(Request $request): User
    {
        $pending = $request->session()->get('admin_mfa.pending', []);
        $user = isset($pending['id']) ? User::find($pending['id']) : null;

        if (! $user || ! $user->requiresAdminMfa()
            || ($pending['expires_at'] ?? 0) <= now()->timestamp
            || ! hash_equals(hash('sha256', $user->getAuthPassword()), (string) ($pending['password_hash'] ?? ''))) {
            $request->session()->forget(['admin_mfa.pending', 'admin_mfa.setup_secret']);
            throw new HttpResponseException(redirect()->route('login')->withErrors(['email' => __('Your sign-in has expired. Please log in again.')]));
        }

        return $user;
    }
}
