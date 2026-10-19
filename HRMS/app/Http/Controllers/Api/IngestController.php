<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InboundWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Identity sync from admin/ (hr_manager & employee users). */
class IngestController extends Controller
{
    public function user(Request $request)
    {
        $secret = (string) env('INTEGRATION_ADMIN_SECRET', '');
        abort_unless($secret !== '' && InboundWebhook::verify($request, $secret), 401, 'bad signature');

        $eventId = (string) $request->input('event_id', (string) Str::uuid());
        $topic = (string) $request->input('topic', 'user.upserted');
        $d = (array) $request->input('data', []);

        InboundWebhook::once($eventId, $topic, function () use ($topic, $d) {
            if ($topic === 'user.deleted') {
                User::where('email', $d['email'])->update(['is_active' => false]);

                return;
            }
            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'phone' => $d['phone'] ?? null,
                    'password' => $d['password'] ?? Hash::make(Str::random(32)),
                    'is_active' => (bool) ($d['is_active'] ?? true),
                ]
            );
            $roles = array_values(array_intersect($d['roles'] ?? [], ['hr_manager', 'employee', 'admin', 'super_admin']));
            $user->syncRoles($roles ?: ['employee']);
        });

        return response()->json(['ok' => true]);
    }
}
