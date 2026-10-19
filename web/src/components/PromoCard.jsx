import { useNavigate } from 'react-router-dom';
import { useCart } from '../context/CartContext';

// Promotions are heterogeneous: some are whole-restaurant/order-level
// discounts with no product behind them (the common case -- e.g. "20% off
// your first order"), others target specific dishes (menu_items populated).
// Render richer product-style detail only when that data actually exists,
// otherwise stay a clean marketing card rather than showing an empty photo
// slot.
export default function PromoCard({ deal }) {
  const navigate = useNavigate();
  const { setCouponCode, openDrawer } = useCart();

  const accent = deal.visibility?.color || null;
  const image = deal.image_url || deal.image || deal.promo_image || deal.thumbnail_url || deal.thumbnail;
  const featuredItem = Array.isArray(deal.menu_items) && deal.menu_items.length > 0 ? deal.menu_items[0] : null;
  const itemImage = featuredItem?.image_url || featuredItem?.image;
  const cover = image || itemImage;

  const restaurantId = deal.restaurant_id ?? deal.targets?.restaurant_ids?.[0] ?? null;
  const targetItemId = featuredItem?.menu_item_id ?? featuredItem?.id ?? deal.targets?.item_ids?.[0] ?? null;
  const couponCode = Array.isArray(deal.coupon_codes) && deal.coupon_codes.length > 0 ? deal.coupon_codes[0] : null;

  function open() {
    if (couponCode) setCouponCode(String(couponCode).toUpperCase());
    if (!restaurantId) {
      // Order-level/platform-wide promo with no single restaurant to jump to
      // (e.g. a wallet or referral-style offer) -- surface it via the cart
      // instead of a dead click.
      if (couponCode) openDrawer();
      return;
    }
    const params = new URLSearchParams({ promo: String(deal.id) });
    if (targetItemId) params.set('item', String(targetItemId));
    navigate(`/restaurants/${restaurantId}?${params.toString()}`);
  }

  return (
    <button
      onClick={open}
      className="group relative flex h-36 w-64 shrink-0 flex-col justify-between overflow-hidden rounded-2xl bg-gradient-to-br from-brand to-brand-strong p-4 text-left text-white shadow-brand transition duration-200 hover:-translate-y-0.5"
      style={accent ? { background: `linear-gradient(135deg, ${accent}, ${shade(accent, -18)})` } : undefined}
    >
      {cover && (
        <img
          src={cover}
          alt=""
          className="absolute inset-0 h-full w-full object-cover opacity-25 transition duration-500 ease-out group-hover:scale-105"
          loading="lazy"
        />
      )}
      <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/25 to-transparent" />
      <div className="relative">
        <p className="text-[15px] font-extrabold leading-tight tracking-tight">{deal.title}</p>
        {(featuredItem?.name || deal.description) && (
          <p className="mt-1 line-clamp-2 text-xs text-white/90">{featuredItem?.name || deal.description}</p>
        )}
      </div>
      <div className="relative mt-3 flex items-center justify-between gap-2 text-sm font-extrabold">
        <span>
          {deal.discount_value != null
            ? deal.discount_type === 'percentage'
              ? `${deal.discount_value}% OFF`
              : `₹${deal.discount_value} OFF`
            : ''}
        </span>
        {couponCode && (
          <span className="rounded-full border border-dashed border-white/60 bg-white/15 px-2 py-0.5 text-[11px] font-bold tracking-wide">
            {couponCode}
          </span>
        )}
      </div>
    </button>
  );
}

function shade(hex, percent) {
  if (!hex?.startsWith('#')) return hex;
  const num = parseInt(hex.slice(1), 16);
  const r = Math.max(0, Math.min(255, ((num >> 16) & 255) + percent));
  const g = Math.max(0, Math.min(255, ((num >> 8) & 255) + percent));
  const b = Math.max(0, Math.min(255, (num & 255) + percent));
  return `rgb(${r}, ${g}, ${b})`;
}
