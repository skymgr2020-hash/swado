<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Theatre;
use App\Models\TheatreRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Movie-theatre in-seat ordering: Phase 4 of the movie-theatre in-seat ordering feature -- the
 * runner-facing claim queue. A theatre_runner reuses the whole existing
 * driver app shell and its order-status/OTP-completion endpoints unchanged
 * (both are already scoped generically by driver_id = auth()->id()), but
 * order *assignment* is different on purpose: runners are never candidates
 * for AutoAssignDriverService's GPS-radius matching (see TheatreManagementService's
 * RUNNER_ROLE comment), since everyone is already inside the same building.
 * Instead a runner browses a shared per-theatre queue of stalls' orders that
 * are ready for pickup and explicitly claims one.
 */
class TheatreRunnerController extends Controller
{
    public function queue(Request $request)
    {
        $theatreId = $this->activeTheatreId();

        $claimable = Order::with(['restaurant:id,name', 'screen:id,name'])
            ->where('theatre_id', $theatreId)
            ->visibleToRestaurant()
            ->where('status', 'ready_for_pickup')
            ->whereNull('driver_id')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => $this->format($order));

        $mine = Order::with(['restaurant:id,name', 'screen:id,name'])
            ->where('theatre_id', $theatreId)
            ->where('driver_id', auth()->id())
            ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Order $order) => $this->format($order));

        return response()->json([
            'success' => true,
            'data' => [
                'claimable' => $claimable,
                'mine' => $mine,
            ],
        ]);
    }

    public function claim($orderId)
    {
        $theatreId = $this->activeTheatreId();

        [$payload, $statusCode] = DB::transaction(function () use ($orderId, $theatreId) {
            $order = Order::where('id', $orderId)
                ->where('theatre_id', $theatreId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return [['success' => false, 'message' => 'Order not found for your theatre.'], 404];
            }

            if ($order->status !== 'ready_for_pickup' || $order->driver_id || ! $order->isVisibleToRestaurant()) {
                return [['success' => false, 'message' => 'This order has already been claimed.'], 409];
            }

            $order->driver_id = auth()->id();
            $order->driver_assigned_at = now();
            $order->driver_accepted_at = now();
            $order->save();

            return [['success' => true, 'message' => 'Order claimed.', 'data' => $this->format($order->fresh(['restaurant', 'screen']))], 200];
        });

        return response()->json($payload, $statusCode);
    }

    private function activeTheatreId(): int
    {
        $user = auth()->user();
        abort_unless($user->is_active && $user->hasRole('theatre_runner'), 403);
        $theatreId = $user->theatre_id;
        abort_unless($theatreId && Theatre::whereKey($theatreId)->where('status', 'active')->exists(), 403, 'Your theatre is not active.');
        abort_unless(TheatreRunner::where('theatre_id', $theatreId)->where('runner_id', $user->id)
            ->where('approval_status', 'active')->exists(), 403, 'Your runner membership is not active.');

        return (int) $theatreId;
    }

    private function format(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'seat_label' => $order->seat_label,
            'screen_id' => $order->screen_id,
            // The runner walks to an auditorium by NAME ("Audi 3"), never
            // by a row id, so send the name alongside it.
            'screen_name' => $order->screen?->name,
            'aisle_handoff_only' => (bool) $order->theatre?->aisle_handoff_only,
            'delivery_timing' => $order->delivery_timing,
            'scheduled_for' => $order->scheduled_for?->toIso8601String(),
            'order_group_id' => $order->order_group_id,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
            ] : null,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
