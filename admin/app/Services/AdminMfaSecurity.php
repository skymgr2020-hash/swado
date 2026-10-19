<?php

namespace App\Services;

use App\Models\AdminMfaChallenge;
use App\Models\User;
use App\Support\AdminMfa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

/**
 * Settings > Security changes for admin MFA (enable, regenerate QR, disable).
 *
 * Every change is authorized with the current password and, while MFA is
 * enabled, a code from the current authenticator or an unused recovery code.
 */
class AdminMfaSecurity
{
    public function context(Request $request): User
    {
        $user = $request->user('web');
        if (! $user || ! $user->requiresAdminMfa() || ! AdminMfa::verified($request, $user)) {
            if ($user) {
                $this->audit($user, 'security_context_rejected', $request);
            }
            abort(403, 'Please sign in with an administrator account and complete its verification.');
        }

        return $user;
    }

    public function start(Request $request): AdminMfaChallenge
    {
        $user = $this->context($request);
        $data = $request->validate([
            'action' => ['required', 'in:reset,enable,disable'],
            'password' => ['required', 'string', 'max:1024'],
            'factor' => ['nullable', 'string', 'max:100'],
        ]);
        $action = $data['action'];

        if ($action === 'enable') {
            abort_if($user->admin_mfa_required, 403);
        } else {
            abort_unless($user->admin_mfa_required && $user->hasEnabledTwoFactorAuthentication(), 403);
        }

        if (! Hash::check((string) $data['password'], $user->getAuthPassword())) {
            $this->audit($user, 'security_password_failed', $request);
            $this->fail('Enter your current password.', 'password');
        }

        $binding = $request->session()->get('admin_security.binding');
        if (! $binding) {
            $binding = Str::random(64);
            $request->session()->put('admin_security.binding', $binding);
        }

        $challenge = DB::transaction(function () use ($request, $user, $data, $action, $binding) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->admin_mfa_version === (int) $user->admin_mfa_version
                && hash_equals($locked->getAuthPassword(), $user->getAuthPassword()), 409);

            if ($locked->admin_mfa_required && ! $this->factor($locked, trim((string) ($data['factor'] ?? '')))) {
                return null; // Commit the consumed-recovery-code state only on success.
            }

            AdminMfaChallenge::where('user_id', $locked->id)->where('session_hash', hash('sha256', $binding))
                ->whereNull('consumed_at')->update(['consumed_at' => now()]);

            $payload = ['authorized_at' => now()->timestamp];
            if (in_array($action, ['reset', 'enable'], true)) {
                $payload['secret'] = Fortify::currentEncrypter()->encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey(32));
            }

            $challenge = new AdminMfaChallenge();
            $challenge->forceFill([
                'id' => (string) Str::uuid(),
                'user_id' => $locked->id,
                'session_hash' => hash('sha256', $binding),
                'security_version' => (int) $locked->admin_mfa_version,
                'password_hash' => hash('sha256', $locked->getAuthPassword()),
                'purpose' => $action,
                'payload' => $payload,
                'otp_hash' => '',
                'expires_at' => now()->addMinutes(10),
                'verified_at' => now(),
            ])->save();

            return $challenge;
        });

        if (! $challenge) {
            $this->audit($user, 'security_factor_failed', $request);
            $this->fail('Enter a valid authenticator or recovery code.', 'factor');
        }

        $request->session()->put('admin_security.challenge', $challenge->id);
        $this->audit($user, 'security_authorized_' . $action, $request);

        return $challenge;
    }

    public function challenge(Request $request, User $user, bool $lock = false): AdminMfaChallenge
    {
        $query = AdminMfaChallenge::whereKey($request->session()->get('admin_security.challenge'));
        $challenge = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $challenge || $challenge->user_id !== $user->id || $challenge->consumed_at || ! $challenge->verified_at
            || $challenge->expires_at->isPast()
            || ($challenge->payload['authorized_at'] ?? 0) + 600 <= now()->timestamp
            || (int) $challenge->security_version !== (int) $user->admin_mfa_version
            || ! hash_equals($challenge->password_hash, hash('sha256', $user->getAuthPassword()))
            || ! hash_equals($challenge->session_hash, hash('sha256', (string) $request->session()->get('admin_security.binding')))) {
            $this->fail('This verification has expired. Start again.');
        }

        return $challenge;
    }

    public function confirm(Request $request): array
    {
        $user = $this->context($request);
        $request->validate(['confirm' => ['required', 'accepted'], 'code' => ['nullable', 'regex:/^[0-9]{6}$/']]);
        $codes = DB::transaction(function () use ($request, &$user) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $challenge = $this->challenge($request, $user, true);
            abort_unless(in_array($challenge->purpose, ['reset', 'enable', 'disable'], true), 403);
            if ($challenge->purpose === 'disable') {
                $user->forceFill(['admin_mfa_required' => false, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
                $codes = [];
            } else {
                $secret = $challenge->payload['secret'];
                if (! app(TwoFactorAuthenticationProvider::class)->verify(Fortify::currentEncrypter()->decrypt($secret), (string) $request->input('code'))) {
                    $this->fail('Enter a valid code from the new authenticator.', 'code');
                }
                $codes = collect(range(1, 8))->map(fn () => RecoveryCode::generate())->all();
                $user->forceFill(['admin_mfa_required' => true, 'two_factor_secret' => $secret,
                    'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($codes)), 'two_factor_confirmed_at' => now()]);
            }
            $this->rotateVersion($user);
            $this->audit($user, 'mfa_' . $challenge->purpose, $request);

            return $codes;
        });
        $request->session()->regenerate();
        $this->syncSession($request, $user);

        return $codes;
    }

    public function factor(User $user, string $code): bool
    {
        if (! $user->hasEnabledTwoFactorAuthentication() || $code === '') {
            return false;
        }
        if (preg_match('/^[0-9]{6}$/', $code)) {
            return app(TwoFactorAuthenticationProvider::class)->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
        }
        foreach ($user->recoveryCodes() as $recovery) {
            if (hash_equals($recovery, $code)) {
                $user->replaceRecoveryCode($recovery);

                return true;
            }
        }

        return false;
    }

    public function rotateVersion(User $user): void
    {
        $user->forceFill(['admin_mfa_version' => (int) $user->admin_mfa_version + 1, 'remember_token' => Str::random(60)])->save();
        AdminMfaChallenge::where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
    }

    public function syncSession(Request $request, User $user): void
    {
        $request->session()->forget('admin_security');
        auth('web')->setUser($user);
        AdminMfa::stamp($request, $user);
    }

    public function cancel(Request $request): void
    {
        $challengeId = $request->session()->get('admin_security.challenge');
        $binding = $request->session()->get('admin_security.binding');
        $userId = $request->user('web')?->id ?? $request->session()->get('admin_mfa.pending.id');
        if ($challengeId && $binding && $userId) {
            AdminMfaChallenge::whereKey($challengeId)
                ->where('user_id', $userId)
                ->where('session_hash', hash('sha256', $binding))
                ->whereNull('consumed_at')->update(['consumed_at' => now()]);
        }
        $request->session()->forget('admin_security');
    }

    public function audit(User $user, string $event, Request $request): void
    {
        Log::info('Admin MFA security event', ['user_id' => $user->id, 'event' => $event, 'ip' => $request->ip()]);
    }

    private function fail(string $message, string $field = 'security'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
