<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return Auth::check() ? redirect(self::home(Auth::user())) : view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        $user = Auth::user();
        if (! $user->hasAnyRole(['hr_manager', 'employee', 'admin', 'super_admin']) || ! ($user->is_active ?? true)) {
            Auth::logout();

            return back()->withErrors(['email' => 'You do not have access to this workspace.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(self::home($user));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function home($user): string
    {
        return $user->hasAnyRole(['hr_manager', 'admin', 'super_admin'])
            ? route('hr.dashboard')
            : route('employee.dashboard');
    }
}
