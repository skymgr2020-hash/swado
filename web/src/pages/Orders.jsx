import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { EmptyState, ErrorState } from '../components/Skeletons';
import { ArrowRightIcon } from '../components/ui/icons';

const LIVE = ['pending', 'placed', 'created', 'confirmed', 'accepted', 'preparing', 'cooking', 'ready', 'picked_up', 'on_the_way', 'out_for_delivery', 'dispatched'];

export default function Orders() {
  const { isAuthed, loading: authLoading, openLogin } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthed) {
      setLoading(false);
      return;
    }
    let cancelled = false;
    api
      .get('/orders', { auth: true })
      .then((res) => !cancelled && setOrders(res?.data?.data ?? []))
      .catch((err) => !cancelled && setError(err.message))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, [isAuthed, authLoading]);

  if (authLoading || loading) return <div className="mx-auto max-w-2xl px-4 py-16 text-center text-sm text-ink-soft">Loading…</div>;

  if (!isAuthed) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16">
        <EmptyState
          title="Log in to see your orders"
          subtitle="Your order history appears here once you're signed in."
          action={
            <button onClick={openLogin} className="btn btn-primary mt-2">
              Log in
            </button>
          }
        />
      </div>
    );
  }

  if (error) return <div className="mx-auto max-w-2xl px-4 py-10"><ErrorState message={error} /></div>;

  const list = Array.isArray(orders) ? orders : [];

  return (
    <div className="mx-auto max-w-2xl px-4 py-6">
      <h1 className="mb-5 font-display text-2xl font-extrabold tracking-tight text-ink">My orders</h1>
      {list.length === 0 && <EmptyState title="No orders yet" subtitle="Place an order to see it here." />}
      <div className="space-y-3">
        {list.map((order) => {
          const status = String(order.status || '').toLowerCase();
          const live = LIVE.includes(status);
          return (
            <Link
              key={order.id}
              to={`/order/${order.id}/track`}
              className="block rounded-2xl border border-line bg-surface p-4 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover"
            >
              <div className="flex items-center justify-between gap-2">
                <p className="font-bold text-ink">{order.order_number || `Order #${order.id}`}</p>
                <span
                  className={`rounded-full px-2.5 py-1 text-xs font-bold capitalize ${
                    live ? 'bg-brand-tint text-brand-strong' : 'bg-surface-alt text-ink-soft'
                  }`}
                >
                  {String(order.status || '').replace(/_/g, ' ')}
                </span>
              </div>
              <p className="mt-1 text-sm text-ink-soft">{order.restaurant?.name || order.restaurant_name}</p>
              <div className="mt-1.5 flex items-center justify-between">
                <p className="text-sm font-extrabold text-ink">₹{Number(order.total ?? 0).toFixed(0)}</p>
                <span className="flex items-center gap-1 text-xs font-bold text-brand">
                  {live ? 'Track' : 'View'} <ArrowRightIcon size={13} />
                </span>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
