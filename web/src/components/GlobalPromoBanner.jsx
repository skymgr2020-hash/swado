import { useCart } from '../context/CartContext';
import { TagIcon } from './ui/icons';

// Sitewide offers (free delivery, blanket first-order %-off, etc.) target no
// specific restaurant/item/category/cuisine -- there's nowhere for a click
// to take the customer, so unlike PromoCard these render as a plain
// informational banner. Still actionable when there's a coupon code: tapping
// pre-fills it into the cart so checkout is one step closer instead of a
// dead end.
export default function GlobalPromoBanner({ deal }) {
  const { setCouponCode, openDrawer } = useCart();
  const accent = deal.visibility?.color || null;
  const rawCoupon = Array.isArray(deal.coupon_codes) && deal.coupon_codes.length > 0 ? deal.coupon_codes[0] : null;
  const couponCode = rawCoupon?.code ?? rawCoupon ?? null;
  const clickable = !!couponCode;

  function open() {
    if (!couponCode) return;
    setCouponCode(String(couponCode).toUpperCase());
    openDrawer();
  }

  return (
    <div
      onClick={clickable ? open : undefined}
      role={clickable ? 'button' : undefined}
      tabIndex={clickable ? 0 : undefined}
      onKeyDown={clickable ? (e) => (e.key === 'Enter' || e.key === ' ') && (e.preventDefault(), open()) : undefined}
      className={`flex items-center justify-between gap-4 overflow-hidden rounded-2xl bg-gradient-to-r from-brand to-brand-strong px-5 py-4 text-white shadow-brand transition ${
        clickable ? 'cursor-pointer hover:brightness-105' : ''
      }`}
      style={accent ? { background: accent } : undefined}
    >
      <div className="flex min-w-0 items-center gap-3">
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15">
          <TagIcon size={18} />
        </span>
        <span className="min-w-0">
          <span className="block font-extrabold tracking-tight">{deal.title}</span>
          {deal.description && <span className="mt-0.5 block text-sm text-white/90">{deal.description}</span>}
        </span>
      </div>
      <div className="flex shrink-0 items-center gap-3">
        {deal.discount_value != null && (
          <span className="hidden text-sm font-extrabold sm:inline">
            {deal.discount_type === 'percentage' ? `${deal.discount_value}% OFF` : `₹${deal.discount_value} OFF`}
          </span>
        )}
        <span className="rounded-full border border-dashed border-white/60 bg-white/15 px-2.5 py-1 text-xs font-bold tracking-wide">
          {couponCode ? couponCode : 'Auto-applied'}
        </span>
      </div>
    </div>
  );
}
