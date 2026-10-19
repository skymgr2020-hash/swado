import { useState } from 'react';
import { useCart } from '../context/CartContext';
import { StarIcon, PlusIcon, MinusIcon } from './ui/icons';
import MenuItemModal from './MenuItemModal';

export default function MenuItemRow({ item, restaurant }) {
  const { cart, addItem, updateQuantity } = useCart();
  const [detailOpen, setDetailOpen] = useState(false);

  const hasOptions =
    (Array.isArray(item.variants) && item.variants.length > 0) ||
    (Array.isArray(item.add_ons ?? item.addons) && (item.add_ons ?? item.addons).length > 0);

  // Plain items key their cart line by the numeric menu-item id; option items
  // can span several lines, so we sum them for the badge.
  const plainLine = cart.items.find((i) => i.id === item.id);
  const totalQty = cart.items
    .filter((i) => (i.menuItemId ?? i.id) === item.id)
    .reduce((s, i) => s + i.quantity, 0);
  const qty = hasOptions ? totalQty : (plainLine?.quantity ?? 0);

  const finalPrice = Number(item.final_price ?? item.discounted_price ?? item.price ?? 0);
  const originalPrice = Number(item.price ?? 0);
  const hasDiscount = item.discounted_price != null && originalPrice > finalPrice;

  // A closed restaurant's menu is browsable but nothing can be added to cart.
  const restaurantClosed = restaurant?.is_open_now === false;

  return (
    <div className="flex items-start justify-between gap-4 border-b border-line py-4 last:border-none">
      <button
        type="button"
        onClick={() => setDetailOpen(true)}
        className="min-w-0 flex-1 text-left"
        aria-label={`View details for ${item.name}`}
      >
        <VegDot isVeg={item.is_veg} />
        {item.is_bestseller && (
          <span className="ml-2 align-middle text-[11px] font-extrabold uppercase tracking-wide text-gold">
            ★ Bestseller
          </span>
        )}
        <p className="mt-1.5 text-[15px] font-bold text-ink">{item.name}</p>
        <p className="mt-0.5 text-sm font-semibold text-ink">
          ₹{finalPrice.toFixed(0)}
          {hasDiscount && <span className="ml-2 text-xs font-normal text-ink-faint line-through">₹{originalPrice.toFixed(0)}</span>}
        </p>
        {item.rating != null && (
          <p className="mt-1 flex items-center gap-1 text-xs font-semibold text-ink-soft">
            <StarIcon size={11} className="text-rating" />
            {Number(item.rating).toFixed(1)}
            {item.total_orders ? <span className="font-normal text-ink-faint"> · {item.total_orders} orders</span> : null}
          </p>
        )}
        {item.description && <p className="mt-1.5 line-clamp-2 text-xs text-ink-soft">{item.description}</p>}
        <span className="mt-1 inline-block text-[11px] font-bold text-brand">View details</span>
      </button>

      <div className="flex w-28 shrink-0 flex-col items-center gap-2">
        {item.image_url && (
          <button type="button" onClick={() => setDetailOpen(true)} aria-label={`View details for ${item.name}`}>
            <img src={item.image_url} alt={item.name} className="h-20 w-24 rounded-xl object-cover" loading="lazy" />
          </button>
        )}
        {restaurantClosed ? (
          <span className="-mt-3 rounded-full border border-line-strong bg-surface-alt px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-ink-faint">
            Closed
          </span>
        ) : !item.is_available ? (
          <span className="text-xs font-semibold text-danger">Unavailable</span>
        ) : hasOptions ? (
          <button
            onClick={() => setDetailOpen(true)}
            className="-mt-3 flex flex-col items-center rounded-full border border-line-strong bg-surface px-5 py-1.5 text-sm font-extrabold uppercase tracking-wide text-brand shadow-card transition hover:border-brand hover:bg-brand-tint"
          >
            {qty > 0 ? `${qty} · Add` : 'Add'}
            <span className="text-[9px] font-bold normal-case tracking-normal text-ink-faint">customisable</span>
          </button>
        ) : qty === 0 ? (
          <button
            onClick={() => addItem(restaurant, item, 1)}
            className="-mt-3 rounded-full border border-line-strong bg-surface px-5 py-1.5 text-sm font-extrabold uppercase tracking-wide text-brand shadow-card transition hover:border-brand hover:bg-brand-tint"
          >
            Add
          </button>
        ) : (
          <div className="stepper -mt-3 text-sm">
            <button onClick={() => updateQuantity(item.id, qty - 1)} aria-label="Decrease quantity">
              <MinusIcon size={15} />
            </button>
            <span className="text-sm font-extrabold">{qty}</span>
            <button onClick={() => addItem(restaurant, item, 1)} aria-label="Increase quantity">
              <PlusIcon size={15} />
            </button>
          </div>
        )}
      </div>

      {detailOpen && (
        <MenuItemModal
          item={item}
          restaurant={restaurant}
          closed={restaurantClosed}
          onClose={() => setDetailOpen(false)}
        />
      )}
    </div>
  );
}

function VegDot({ isVeg }) {
  return (
    <span
      className={`inline-flex h-3.5 w-3.5 items-center justify-center border p-0.5 ${
        isVeg ? 'border-veg' : 'border-nonveg'
      }`}
      style={{ borderRadius: 3 }}
    >
      <span className={`block h-full w-full rounded-full ${isVeg ? 'bg-veg' : 'bg-nonveg'}`} />
    </span>
  );
}
