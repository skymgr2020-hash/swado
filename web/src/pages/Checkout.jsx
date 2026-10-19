import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, friendlyError } from '../api/client';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useBranding } from '../context/BrandingContext';
import { useUserLocation } from '../context/LocationContext';
import BillDetails from '../components/order/BillDetails';
import PaymentMethodSelector from '../components/checkout/PaymentMethodSelector';
import { EmptyState, ErrorState } from '../components/Skeletons';
import { PinIcon, PlusIcon, MinusIcon, CheckIcon, TagIcon } from '../components/ui/icons';
import { openRazorpayCheckout } from '../lib/razorpay';

function SectionCard({ step, title, action, children }) {
  return (
    <section className="rounded-2xl border border-line bg-surface p-4 shadow-card sm:p-5">
      <div className="mb-3 flex items-center justify-between gap-3">
        <h2 className="flex items-center gap-2 font-display text-base font-extrabold tracking-tight text-ink">
          {step != null && (
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-brand-tint text-xs font-extrabold text-brand-strong">
              {step}
            </span>
          )}
          {title}
        </h2>
        {action}
      </div>
      {children}
    </section>
  );
}

function lineOptionText(item) {
  const bits = [];
  if (item.variant?.name) bits.push(item.variant.name);
  if (item.addOns?.length) bits.push(item.addOns.map((a) => a.name).join(', '));
  return bits.join(' · ');
}

export default function Checkout() {
  const navigate = useNavigate();
  const { cart, updateQuantity, orderItems, subtotal, couponCode, setCouponCode, clearCart } = useCart();
  const { isAuthed, user, loading: authLoading, openLogin } = useAuth();
  const { branding } = useBranding();
  const { location, openPicker } = useUserLocation();

  const [addresses, setAddresses] = useState([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [selectedId, setSelectedId] = useState(null);

  const [summary, setSummary] = useState(null);
  const [summaryLoading, setSummaryLoading] = useState(false);
  const [summaryError, setSummaryError] = useState(null);

  const [payment, setPayment] = useState('cod');
  const [walletBalance, setWalletBalance] = useState(null);
  const [instructions, setInstructions] = useState('');
  const [couponDraft, setCouponDraft] = useState(couponCode || '');

  const [placing, setPlacing] = useState(false);
  const [placeError, setPlaceError] = useState(null);
  const placedRef = useRef(false);

  const empty = cart.items.length === 0;
  const gatewayProvider = (user?.settings?.payment_gateway_provider || 'razorpay').toLowerCase();
  const onlineAvailable = user?.settings?.payment_gateway_enabled !== false && gatewayProvider === 'razorpay';

  useEffect(() => {
    if (empty && !placedRef.current) navigate('/', { replace: true });
  }, [empty, navigate]);

  useEffect(() => {
    if (!isAuthed) {
      setAddresses([]);
      return undefined;
    }
    let cancelled = false;
    setAddressesLoading(true);
    api
      .get('/addresses', { auth: true })
      .then((res) => !cancelled && setAddresses(res?.data ?? []))
      .catch(() => !cancelled && setAddresses([]))
      .finally(() => !cancelled && setAddressesLoading(false));
    api
      .get('/wallet', { auth: true })
      .then((res) => {
        if (cancelled) return;
        const w = res?.data?.wallet ?? res?.data ?? {};
        const bal = Number(w.balance ?? w.amount ?? 0);
        setWalletBalance(Number.isFinite(bal) ? bal : 0);
      })
      .catch(() => !cancelled && setWalletBalance(0));
    return () => {
      cancelled = true;
    };
  }, [isAuthed]);

  const mapOption = location
    ? {
        id: '__map__',
        name: location.label?.split(',')[0] || 'Selected location',
        address: location.label || 'Selected location',
        latitude: location.lat,
        longitude: location.lng,
        is_deliverable: true,
        _map: true,
      }
    : null;

  const options = useMemo(() => {
    const list = [...addresses];
    if (mapOption) list.push(mapOption);
    return list;
  }, [addresses, mapOption?.latitude, mapOption?.longitude, mapOption?.address]);

  useEffect(() => {
    if (selectedId && options.some((o) => String(o.id) === String(selectedId))) return;
    const preferred = addresses.find((a) => a.is_default) || addresses[0] || mapOption || null;
    if (preferred) setSelectedId(String(preferred.id));
  }, [options, addresses, mapOption?.address]); // eslint-disable-line react-hooks/exhaustive-deps

  const selected = options.find((o) => String(o.id) === String(selectedId)) || null;
  const deliveryLat = selected?.latitude ?? location?.lat ?? null;
  const deliveryLng = selected?.longitude ?? location?.lng ?? null;
  const hasDeliveryPoint = deliveryLat != null && deliveryLng != null;

  useEffect(() => {
    if (empty || !hasDeliveryPoint || !isAuthed) {
      setSummary(null);
      return undefined;
    }
    let cancelled = false;
    setSummaryLoading(true);
    setSummaryError(null);
    api
      .post('/orders/summary', {
        restaurant_id: cart.restaurantId,
        items: orderItems,
        order_type: 'delivery',
        ...(selected && !selected._map ? { delivery_address_id: selected.id } : {}),
        delivery_lat: deliveryLat,
        delivery_lng: deliveryLng,
        coupon_code: couponCode || undefined,
      })
      .then((res) => !cancelled && setSummary(res?.data ?? null))
      .catch((err) => !cancelled && setSummaryError(friendlyError(err, "We couldn't load your order summary.")))
      .finally(() => !cancelled && setSummaryLoading(false));
    return () => {
      cancelled = true;
    };
  }, [empty, isAuthed, hasDeliveryPoint, deliveryLat, deliveryLng, selectedId, couponCode, orderItems, cart.restaurantId]);

  const total = Number(summary?.total ?? subtotal);

  function orderPayload(phone, methodOverride) {
    return {
      restaurant_id: cart.restaurantId,
      items: orderItems,
      order_type: 'delivery',
      ...(selected && !selected._map ? { delivery_address_id: selected.id } : {}),
      delivery_address: selected?.address || location?.label || '',
      delivery_lat: deliveryLat,
      delivery_lng: deliveryLng,
      customer_name: user?.name,
      customer_email: user?.email,
      email: user?.email,
      customer_phone: phone,
      phone,
      contact: phone,
      payment_method: methodOverride || payment,
      special_instructions: instructions.trim(),
      coupon_code: couponCode || undefined,
    };
  }

  function finish(order, methodLabel) {
    placedRef.current = true;
    navigate('/order/confirmation', { replace: true, state: { order, paymentMethod: methodLabel } });
    clearCart();
    setCouponCode('');
  }

  async function placeOrder() {
    if (!hasDeliveryPoint) {
      setPlaceError('Add a delivery address to continue.');
      return;
    }
    if (selected && selected.is_deliverable === false) {
      setPlaceError('The selected address is outside the delivery area.');
      return;
    }
    setPlacing(true);
    setPlaceError(null);
    const phone = (selected?.phone || user?.phone || '').trim();

    try {
      if (payment === 'online') {
        const create = await api.post(
          '/payments/checkout/create',
          {
            ...orderPayload(phone, 'razorpay'),
            gateway: 'razorpay',
            payment_gateway: 'razorpay',
            payment_gateway_provider: 'razorpay',
          },
          { auth: true }
        );
        const d = create?.data ?? {};
        const token = d.checkout_payment_token ?? d.payment_token;
        const gw = String(d.gateway ?? 'razorpay').toLowerCase();
        if (gw !== 'razorpay') {
          throw new Error('Online payment for this store is only available in the Swado app. Please pick Cash on delivery.');
        }
        const key = d.key ?? d.razorpay_key;
        const rzpOrderId = d.order_id ?? d.razorpay_order_id ?? d.gateway_reference;
        const amount = Number(d.amount ?? d.amount_minor);
        if (!key || !rzpOrderId || !amount) {
          throw new Error('Could not start the payment. Please try again.');
        }
        const result = await openRazorpayCheckout({
          key,
          amount,
          currency: d.currency || 'INR',
          orderId: rzpOrderId,
          name: branding?.app_name || 'Swado',
          description: `Order at ${cart.restaurantName || 'restaurant'}`,
          prefill: { name: user?.name, email: user?.email, contact: phone },
        });
        if (!result) {
          setPlaceError('Payment cancelled. Your order was not placed.');
          setPlacing(false);
          return;
        }
        const verify = await api.post(
          '/payments/checkout/verify',
          {
            checkout_payment_token: token,
            payment_method: 'razorpay',
            gateway: 'razorpay',
            payment_gateway: 'razorpay',
            payment_gateway_provider: 'razorpay',
            ...result,
          },
          { auth: true }
        );
        const vd = verify?.data ?? {};
        finish(vd.order ?? vd, 'online');
        return;
      }

      const res = await api.post('/orders', orderPayload(phone), { auth: true });
      const payload = res?.data ?? {};
      finish(payload.order ?? payload, payment);
    } catch (err) {
      setPlaceError(friendlyError(err, 'Could not place the order. Please try again.'));
      setPlacing(false);
    }
  }

  function applyCoupon() {
    setCouponCode(couponDraft.trim().toUpperCase());
  }

  // Login is required to reach checkout (the cart drawer prompts first), but
  // guard here too for deep links / session expiry.
  if (!authLoading && !isAuthed) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16">
        <EmptyState
          title="Log in to check out"
          subtitle="Sign in to choose an address, apply offers and place your order."
          action={
            <button onClick={openLogin} className="btn btn-primary mt-2">
              Log in
            </button>
          }
        />
      </div>
    );
  }
  if (authLoading || empty) {
    return <div className="mx-auto max-w-3xl px-4 py-16 text-center text-sm text-ink-soft">Loading…</div>;
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-6">
      <div className="mb-5 flex items-center justify-between">
        <h1 className="font-display text-2xl font-extrabold tracking-tight text-ink">Checkout</h1>
        <button onClick={() => navigate(-1)} className="text-sm font-bold text-brand">
          Back to cart
        </button>
      </div>

      <div className="space-y-4 pb-28">
        {/* 1 — Delivery address */}
        <SectionCard
          step={1}
          title="Delivery address"
          action={
            <button onClick={openPicker} className="text-xs font-bold text-brand">
              {options.length ? 'Change' : 'Add address'}
            </button>
          }
        >
          {addressesLoading && <p className="text-sm text-ink-soft">Loading saved addresses…</p>}
          {!addressesLoading && options.length === 0 && (
            <button
              onClick={openPicker}
              className="flex w-full items-center gap-2 rounded-xl border border-dashed border-line-strong bg-surface-alt px-4 py-3 text-sm font-semibold text-ink-soft transition hover:border-brand hover:text-brand"
            >
              <PinIcon size={18} />
              Set your delivery location
            </button>
          )}
          <div className="space-y-2">
            {options.map((o) => {
              const active = String(o.id) === String(selectedId);
              return (
                <button
                  key={o.id}
                  onClick={() => setSelectedId(String(o.id))}
                  className={`flex w-full items-start gap-3 rounded-xl border p-3 text-left transition ${
                    active ? 'border-brand bg-brand-tint' : 'border-line-strong bg-surface hover:border-brand'
                  }`}
                >
                  <PinIcon size={18} className={`mt-0.5 shrink-0 ${active ? 'text-brand' : 'text-ink-faint'}`} />
                  <span className="min-w-0 flex-1">
                    <span className="flex items-center gap-2 text-sm font-bold text-ink">
                      {o.name || 'Address'}
                      {o._map && <span className="rounded-full bg-surface-alt px-1.5 py-0.5 text-[10px] font-bold text-ink-soft">MAP PIN</span>}
                      {o.is_default && <span className="rounded-full bg-surface-alt px-1.5 py-0.5 text-[10px] font-bold text-ink-soft">DEFAULT</span>}
                    </span>
                    <span className="mt-0.5 line-clamp-2 block text-xs text-ink-soft">{o.address}</span>
                    {o.is_deliverable === false && (
                      <span className="mt-1 block text-xs font-bold text-danger">Outside delivery area</span>
                    )}
                  </span>
                  <span className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${active ? 'border-brand bg-brand text-white' : 'border-line-strong'}`}>
                    {active && <CheckIcon size={12} />}
                  </span>
                </button>
              );
            })}
          </div>
        </SectionCard>

        {/* 2 — Order items */}
        <SectionCard step={2} title={`Your order · ${cart.restaurantName || ''}`}>
          <div className="divide-y divide-line">
            {cart.items.map((item) => {
              const opt = lineOptionText(item);
              return (
                <div key={item.id} className="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-ink">{item.name}</p>
                    {opt && <p className="truncate text-xs text-ink-soft">{opt}</p>}
                  </div>
                  <div className="stepper text-sm">
                    <button onClick={() => updateQuantity(item.id, item.quantity - 1)} aria-label="Decrease quantity">
                      <MinusIcon size={14} />
                    </button>
                    <span className="text-xs font-extrabold">{item.quantity}</span>
                    <button onClick={() => updateQuantity(item.id, item.quantity + 1)} aria-label="Increase quantity">
                      <PlusIcon size={14} />
                    </button>
                  </div>
                  <span className="w-16 shrink-0 text-right text-sm font-extrabold text-ink">
                    ₹{(item.price * item.quantity).toFixed(0)}
                  </span>
                </div>
              );
            })}
          </div>
          <label className="mt-3 block">
            <span className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-ink-faint">
              Delivery instructions
            </span>
            <textarea
              value={instructions}
              onChange={(e) => setInstructions(e.target.value)}
              rows={2}
              placeholder="e.g. Leave at the door, call on arrival"
              className="field resize-none"
            />
          </label>
        </SectionCard>

        {/* 3 — Coupon */}
        <SectionCard step={3} title="Offers & coupons">
          <div className="flex gap-2">
            <span className="flex items-center pl-1 text-ink-faint">
              <TagIcon size={18} />
            </span>
            <input
              value={couponDraft}
              onChange={(e) => setCouponDraft(e.target.value.toUpperCase())}
              placeholder="Enter coupon code"
              className="field"
            />
            {couponCode ? (
              <button
                onClick={() => {
                  setCouponCode('');
                  setCouponDraft('');
                }}
                className="shrink-0 rounded-xl border border-line-strong px-3 text-xs font-bold text-ink-soft transition hover:border-brand hover:text-brand"
              >
                Remove
              </button>
            ) : (
              <button onClick={applyCoupon} className="btn btn-outline shrink-0 px-4 py-2">
                Apply
              </button>
            )}
          </div>
          {couponCode && summary && Number(summary.discount ?? 0) > 0 && (
            <p className="mt-2 flex items-center gap-1.5 text-xs font-bold text-veg">
              <CheckIcon size={13} /> {couponCode} applied — you saved ₹{Number(summary.discount).toFixed(0)}
            </p>
          )}
          {couponCode && summary && !(Number(summary.discount ?? 0) > 0) && (
            <p className="mt-2 text-xs font-semibold text-danger">{couponCode} could not be applied to this order.</p>
          )}
        </SectionCard>

        {/* 4 — Payment */}
        <SectionCard step={4} title="Payment method">
          <PaymentMethodSelector
            value={payment}
            onChange={setPayment}
            walletBalance={walletBalance}
            total={total}
            onlineAvailable={onlineAvailable}
          />
        </SectionCard>

        {/* 5 — Bill */}
        <SectionCard step={5} title="Bill details">
          {!hasDeliveryPoint && <p className="text-sm text-ink-soft">Choose a delivery address to see the bill.</p>}
          {hasDeliveryPoint && summaryLoading && !summary && (
            <p className="text-sm text-ink-soft">Calculating live pricing…</p>
          )}
          {summaryError && <ErrorState message={summaryError} />}
          {summary && <BillDetails summary={summary} />}
        </SectionCard>

        {placeError && (
          <p className="rounded-xl bg-[color-mix(in_oklab,var(--color-danger),#fff_86%)] p-3 text-sm font-semibold text-danger">
            {placeError}
          </p>
        )}
      </div>

      {/* Sticky place-order bar */}
      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur-md">
        <div className="mx-auto flex max-w-3xl items-center gap-4 px-4 py-3">
          <div className="min-w-0">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-ink-faint">To pay</p>
            <p className="text-lg font-extrabold text-ink">₹{total.toFixed(0)}</p>
          </div>
          <button
            onClick={placeOrder}
            disabled={placing || summaryLoading || !hasDeliveryPoint}
            className="btn btn-primary flex-1 py-3"
          >
            {placing
              ? 'Processing…'
              : payment === 'cod'
                ? 'Place order · Cash on delivery'
                : `Pay ₹${total.toFixed(0)} · ${payment === 'wallet' ? 'Wallet' : 'Online'}`}
          </button>
        </div>
      </div>
    </div>
  );
}
