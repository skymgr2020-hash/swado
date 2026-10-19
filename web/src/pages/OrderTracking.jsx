import { useCallback, useEffect, useRef, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import OrderStatusTimeline from '../components/order/OrderStatusTimeline';
import TrackingMap from '../components/order/TrackingMap';
import BillDetails from '../components/order/BillDetails';
import { EmptyState, ErrorState } from '../components/Skeletons';
import { PhoneIcon, BikeIcon, ClockIcon, StoreIcon, PinIcon } from '../components/ui/icons';

const TERMINAL = ['delivered', 'completed', 'cancelled', 'canceled', 'rejected', 'refunded'];
const CANCELLABLE = ['pending', 'placed', 'created', 'confirmed', 'accepted'];
const POLL_MS = 15000;

function etaText(order) {
  const range = order?.eta_range ?? order?.eta?.range;
  if (range) return range;
  const mins = order?.eta_minutes ?? order?.eta?.minutes;
  if (mins) return `${mins} min`;
  return null;
}

export default function OrderTracking() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAuthed, loading: authLoading, openLogin } = useAuth();

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const timerRef = useRef(null);

  const load = useCallback(
    async ({ silent = false } = {}) => {
      if (!silent) setLoading(true);
      try {
        const res = await api.get(`/orders/${id}`, { auth: true });
        const data = res?.data?.order ?? res?.data ?? null;
        setOrder(data);
        setError(null);
        return data;
      } catch (err) {
        if (!silent) setError(err.message || 'Could not load this order.');
        return null;
      } finally {
        if (!silent) setLoading(false);
      }
    },
    [id]
  );

  useEffect(() => {
    if (authLoading) return undefined;
    if (!isAuthed) {
      setLoading(false);
      return undefined;
    }
    let stopped = false;
    load().then((data) => {
      if (stopped) return;
      const status = String(data?.status || '').toLowerCase();
      if (data && !TERMINAL.includes(status)) {
        timerRef.current = setInterval(async () => {
          const fresh = await load({ silent: true });
          const s = String(fresh?.status || '').toLowerCase();
          if (!fresh || TERMINAL.includes(s)) {
            clearInterval(timerRef.current);
            timerRef.current = null;
          }
        }, POLL_MS);
      }
    });
    return () => {
      stopped = true;
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [id, isAuthed, authLoading, load]);

  async function cancelOrder() {
    const reason = window.prompt('Tell us why you are cancelling (optional):', '') ?? '';
    setCancelling(true);
    try {
      await api.post(`/orders/${id}/cancel`, { reason }, { auth: true });
      await load({ silent: true });
    } catch (err) {
      setError(err.message || 'Could not cancel the order.');
    } finally {
      setCancelling(false);
    }
  }

  if (authLoading || loading) {
    return <div className="mx-auto max-w-3xl px-4 py-16 text-center text-sm text-ink-soft">Loading order…</div>;
  }

  if (!isAuthed) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16">
        <EmptyState
          title="Log in to track this order"
          subtitle="Order tracking is tied to your account."
          action={
            <button onClick={openLogin} className="btn btn-primary mt-2">
              Log in
            </button>
          }
        />
      </div>
    );
  }

  if (error && !order) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-10">
        <ErrorState message={error} onRetry={() => load()} />
      </div>
    );
  }
  if (!order) return null;

  const status = String(order.status || '').toLowerCase();
  const isTerminal = TERMINAL.includes(status);
  const canCancel = CANCELLABLE.includes(status);
  const eta = etaText(order);
  const driver = order.driver;
  const showOtp =
    order.delivery_otp && ['on_the_way', 'out_for_delivery', 'dispatched', 'ready', 'picked_up'].includes(status);
  const orderNumber = order.order_number || order.orderNumber || `ORD${order.id}`;
  const restaurantName = order.restaurant?.name || order.restaurant_name || 'Restaurant';

  return (
    <div className="mx-auto max-w-3xl px-4 py-6">
      <div className="mb-5 flex items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-extrabold tracking-tight text-ink">Track order</h1>
          <p className="text-sm text-ink-soft">
            {orderNumber} · {restaurantName}
          </p>
        </div>
        <Link to="/orders" className="text-sm font-bold text-brand">
          All orders
        </Link>
      </div>

      {/* Hero: ETA + map */}
      <div className="overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
        <div className="flex items-center gap-3 border-b border-line p-4">
          <span className="flex h-11 w-11 items-center justify-center rounded-full bg-brand-tint text-brand">
            {isTerminal ? <StoreIcon size={20} /> : <BikeIcon size={20} />}
          </span>
          <div className="min-w-0">
            <p className="text-sm font-extrabold text-ink">
              {status === 'delivered'
                ? 'Delivered'
                : status === 'cancelled' || status === 'canceled'
                  ? 'Order cancelled'
                  : eta
                    ? `Arriving in about ${eta}`
                    : 'Your order is on its way'}
            </p>
            {!isTerminal && (
              <p className="flex items-center gap-1 text-xs text-ink-soft">
                <ClockIcon size={13} />
                Live status updates automatically
              </p>
            )}
          </div>
        </div>
        <div className="h-56 p-3">
          <TrackingMap order={order} />
        </div>
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-[1fr_1.1fr] lg:items-start">
        {/* Timeline + driver */}
        <div className="space-y-4">
          <section className="rounded-2xl border border-line bg-surface p-5 shadow-card">
            <h2 className="mb-4 font-display text-base font-extrabold tracking-tight text-ink">Progress</h2>
            <OrderStatusTimeline status={order.status} />
          </section>

          {driver && (
            <section className="rounded-2xl border border-line bg-surface p-4 shadow-card">
              <div className="flex items-center gap-3">
                <span className="flex h-11 w-11 items-center justify-center rounded-full bg-surface-alt text-ink-soft">
                  <BikeIcon size={20} />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-bold text-ink">{driver.name || 'Delivery partner'}</p>
                  <p className="text-xs text-ink-soft">Your delivery partner</p>
                </div>
                {driver.phone && (
                  <a href={`tel:${driver.phone}`} className="btn btn-outline px-3 py-2 text-xs">
                    <PhoneIcon size={14} />
                    Call
                  </a>
                )}
              </div>
            </section>
          )}

          {showOtp && (
            <section className="rounded-2xl border border-brand bg-brand-tint p-4">
              <p className="text-xs font-bold uppercase tracking-wide text-brand-strong">Delivery OTP</p>
              <p className="mt-1 font-display text-2xl font-extrabold tracking-[0.3em] text-ink">
                {order.delivery_otp}
              </p>
              <p className="mt-1 text-xs text-ink-soft">Share this with your delivery partner on arrival.</p>
            </section>
          )}
        </div>

        {/* Order details */}
        <div className="space-y-4">
          <section className="rounded-2xl border border-line bg-surface p-5 shadow-card">
            <h2 className="mb-3 font-display text-base font-extrabold tracking-tight text-ink">Order details</h2>
            <div className="divide-y divide-line">
              {(order.items || []).map((it, idx) => (
                <div key={it.id ?? idx} className="flex justify-between gap-3 py-2 text-sm first:pt-0">
                  <span className="min-w-0 text-ink">
                    <span className="font-semibold">{it.quantity ?? it.qty ?? 1}×</span> {it.name || it.item_name}
                  </span>
                  <span className="shrink-0 font-semibold text-ink-soft">
                    ₹{Number(it.total ?? it.price ?? it.line_total ?? 0).toFixed(0)}
                  </span>
                </div>
              ))}
            </div>
            <div className="mt-3 border-t border-line pt-3">
              <BillDetails summary={order} />
            </div>
          </section>

          {(order.delivery_address || order.delivery_lat != null) && (
            <section className="rounded-2xl border border-line bg-surface p-4 shadow-card">
              <div className="flex gap-2.5">
                <PinIcon size={18} className="mt-0.5 shrink-0 text-ink-faint" />
                <div>
                  <p className="text-xs font-bold uppercase tracking-wide text-ink-faint">Delivering to</p>
                  <p className="mt-0.5 text-sm text-ink">{order.delivery_address || 'Selected map location'}</p>
                </div>
              </div>
            </section>
          )}

          {error && <p className="text-sm font-semibold text-danger">{error}</p>}

          {canCancel && (
            <button
              onClick={cancelOrder}
              disabled={cancelling}
              className="w-full rounded-full border border-line-strong py-2.5 text-sm font-bold text-danger transition hover:border-danger disabled:opacity-60"
            >
              {cancelling ? 'Cancelling…' : 'Cancel order'}
            </button>
          )}
          {status === 'delivered' && (
            <button onClick={() => navigate('/orders')} className="btn btn-primary w-full">
              Done
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
