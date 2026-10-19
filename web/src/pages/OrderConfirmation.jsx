import { useLocation, useNavigate, Link } from 'react-router-dom';
import { CheckIcon, ArrowRightIcon, ReceiptIcon } from '../components/ui/icons';

const PAYMENT_LABEL = {
  cod: 'Cash on delivery',
  wallet: 'Swado Wallet',
  online: 'Paid online',
};

export default function OrderConfirmation() {
  const navigate = useNavigate();
  const { state } = useLocation();
  const order = state?.order ?? null;
  const paymentMethod = state?.paymentMethod;

  const orderId = order?.id ?? order?.order?.id ?? null;
  const orderNumber = order?.order_number ?? order?.orderNumber ?? (orderId ? `ORD${orderId}` : null);
  const total = Number(order?.total ?? order?.grand_total ?? 0);
  const restaurantName = order?.restaurant?.name ?? order?.restaurant_name ?? '';
  const paymentStatus = String(order?.payment_status ?? '').toLowerCase();

  return (
    <div className="mx-auto max-w-lg px-4 py-12">
      <div className="rounded-3xl border border-line bg-surface p-6 text-center shadow-card sm:p-8">
        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[color-mix(in_oklab,var(--color-veg),#fff_82%)] text-veg">
          <CheckIcon size={32} />
        </span>
        <h1 className="mt-4 font-display text-2xl font-extrabold tracking-tight text-ink">Order placed!</h1>
        <p className="mt-1 text-sm text-ink-soft">
          {restaurantName ? `${restaurantName} has received your order.` : 'Your order has been received.'}
        </p>

        {(orderNumber || total > 0) && (
          <dl className="mt-5 space-y-2 rounded-2xl bg-surface-alt p-4 text-left text-sm">
            {orderNumber && (
              <div className="flex justify-between">
                <dt className="text-ink-soft">Order</dt>
                <dd className="font-bold text-ink">{orderNumber}</dd>
              </div>
            )}
            {total > 0 && (
              <div className="flex justify-between">
                <dt className="text-ink-soft">Amount</dt>
                <dd className="font-bold text-ink">₹{total.toFixed(0)}</dd>
              </div>
            )}
            {paymentMethod && (
              <div className="flex justify-between">
                <dt className="text-ink-soft">Payment</dt>
                <dd className="font-bold text-ink">
                  {PAYMENT_LABEL[paymentMethod] || paymentMethod}
                  {paymentStatus && paymentStatus !== 'paid' && paymentMethod !== 'cod' && (
                    <span className="ml-1 text-xs font-semibold capitalize text-ink-soft">({paymentStatus})</span>
                  )}
                </dd>
              </div>
            )}
          </dl>
        )}

        <div className="mt-6 flex flex-col gap-2.5">
          {orderId && (
            <button onClick={() => navigate(`/order/${orderId}/track`)} className="btn btn-primary w-full py-3">
              Track order
              <ArrowRightIcon size={16} />
            </button>
          )}
          <Link to="/orders" className="btn btn-outline w-full py-3">
            <ReceiptIcon size={16} />
            My orders
          </Link>
          <Link to="/" className="mt-1 text-sm font-bold text-brand">
            Back to home
          </Link>
        </div>
      </div>
    </div>
  );
}
