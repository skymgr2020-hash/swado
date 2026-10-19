<?php

namespace App\Http\Middleware;

use App\Support\AdminMfa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdminMfa
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->requiresAdminMfa() && ! $request->routeIs('logout') && ! AdminMfa::verified($request, $user)) {
            // Also challenge sessions restored by remember-me or alternate web login paths.
            Auth::guard('web')->logoutCurrentDevice();

            return AdminMfa::begin($request, $user);
        }

        return $next($request);
    }
}
