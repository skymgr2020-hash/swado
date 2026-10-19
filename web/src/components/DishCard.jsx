import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { StarIcon, PlusIcon, MinusIcon } from './ui/icons';
import { useCart } from '../context/CartContext';
import MenuItemModal from './MenuItemModal';

// Regular dish items (popular_dishes) and reshaped combo cards
// (comboGroupPromotionCards() on the backend, tagged card_type:'combo_group')
// use the field for opposite meanings: on a plain dish, `price` is the
// pre-discount price and `discounted_price` is the sale price; on a combo
// card, `price` IS already the final bundle price and `actual_price` is
// what it was reduced from. Branch on the explicit `card_type` flag rather
// than guessing from which fields happen to be present.
export default function DishCard({ item }) {
  const navigate = useNavigate();
  const { cart, addItem, updateQuantity } = useCart();
  const [detailOpen, setDetailOpen] = useState(false);
  const isCombo = item.card_type === 'combo_group';

  const finalPrice = isCombo
    ? Number(item.price ?? item.effective_price ?? 0)
    : Number(item.discounted_price ?? item.final_price ?? item.price ?? 0);
  const originalPrice = isCombo
    ? Number(item.actual_price ?? item.original_price ?? finalPrice)
    : Number(item.price ?? 0);
  const hasDiscount = isCombo ? originalPrice > finalPrice : item.discounted_price != null && originalPrice > finalPrice;

  // A combo card's `item.id` is a Promotion id, not a menu item, and a
  // bundle needs promo context to price correctly -- those still route to
  // the restaurant page. A plain dish can go straight into the cart when we
  // have a restaurant + a real menu item id AND no variants/add-ons to pick
  // (those go through the detail popup).
  const menuItemId = item.menu_item_id ?? item.id;
  const hasOptions =
    (Array.isArray(item.variants) && item.variants.length > 0) ||
    (Array.isArray(item.add_ons ?? item.addons) && (item.add_ons ?? item.addons).length > 0);
  const canQuickAdd =
    !isCombo && !hasOptions && item.restaurant_id != null && menuItemId != null && item.is_available !== false;

  const inCart = canQuickAdd ? cart.items.find((i) => i.id === menuItemId) : null;
  const qty = inCart?.quantity ?? 0;

  function open() {
    // Plain dish -> full-detail popup. Combo bundle -> restaurant page with
    // promo context (a bundle can't be priced/added without it).
    if (!isCombo) {
      setDetailOpen(true);
      return;
    }
    if (!item.restaurant_id) return;
    const params = new URLSearchParams();
    if (item.id) params.set('promo', String(item.id));
    const firstDish = item.menu_items?.[0];
    const dishId = firstDish?.menu_item_id ?? firstDish?.id;
    if (dishId) params.set('item', String(dishId));
    const query = params.toString();
    navigate(`/restaurants/${item.restaurant_id}${query ? `?${query}` : ''}`);
  }

  function add() {
    addItem(
      { id: item.restaurant_id, name: item.restaurant_name },
      {
        id: menuItemId,
        name: item.name,
        final_price: finalPrice,
        image_url: item.image_url || item.image || null,
        is_veg: item.is_veg,
      },
      1
    );
  }

  return (
    <div className="group relative flex w-44 shrink-0 flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-card transition duration-200 hover:-translate-y-0.5 hover:shadow-card-hover">
      <button type="button" onClick={open} className="block w-full text-left">
        <span className="relative block aspect-square w-full bg-surface-alt">
          {item.image_url || item.image ? (
            <img
              src={item.image_url || item.image}
              alt={item.name}
              className="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-105"
              loading="lazy"
            />
          ) : (
            <span className="flex h-full w-full items-center justify-center text-xs text-ink-faint">No image</span>
          )}
          {item.is_veg != null && <VegDot isVeg={item.is_veg} className="absolute left-2 top-2" />}
          {isCombo && (
            <span className="absolute right-2 top-2 rounded-full bg-brand px-2 py-0.5 text-[10px] font-extrabold tracking-wide text-white">
              COMBO
            </span>
          )}
        </span>
        <span className="block px-3 pt-3">
          <span className="line-clamp-1 block text-sm font-bold text-ink">{item.name}</span>
          {isCombo && item.subtitle ? (
            <span className="line-clamp-1 block text-xs text-ink-soft">{item.subtitle}</span>
          ) : (
            item.restaurant_name && <span className="line-clamp-1 block text-xs text-ink-soft">{item.restaurant_name}</span>
          )}
        </span>
      </button>

      <div className="mt-auto flex items-end justify-between gap-2 px-3 pb-3 pt-1.5">
        <div className="min-w-0">
          <div className="flex items-baseline gap-1.5">
            <span className="text-sm font-extrabold text-ink">₹{finalPrice.toFixed(0)}</span>
            {hasDiscount && <span className="text-xs text-ink-faint line-through">₹{originalPrice.toFixed(0)}</span>}
          </div>
          {item.rating > 0 && (
            <p className="mt-1 flex items-center gap-1 text-xs font-semibold text-ink-soft">
              <StarIcon size={11} className="text-rating" />
              {Number(item.rating).toFixed(1)}
            </p>
          )}
        </div>

        {canQuickAdd &&
          (qty === 0 ? (
            <button
              type="button"
              onClick={add}
              className="shrink-0 rounded-full border border-line-strong bg-surface px-3.5 py-1.5 text-xs font-extrabold uppercase tracking-wide text-brand shadow-card transition hover:border-brand hover:bg-brand-tint"
              aria-label={`Add ${item.name} to cart`}
            >
              Add
            </button>
          ) : (
            <div className="stepper shrink-0">
              <button type="button" onClick={() => updateQuantity(menuItemId, qty - 1)} aria-label="Decrease quantity">
                <MinusIcon size={14} />
              </button>
              <span className="text-xs font-extrabold">{qty}</span>
              <button type="button" onClick={add} aria-label="Increase quantity">
                <PlusIcon size={14} />
              </button>
            </div>
          ))}
      </div>

      {detailOpen && (
        <MenuItemModal
          item={item}
          restaurant={item.restaurant_id != null ? { id: item.restaurant_id, name: item.restaurant_name } : null}
          onClose={() => setDetailOpen(false)}
        />
      )}
    </div>
  );
}

function VegDot({ isVeg, className = '' }) {
  return (
    <span
      className={`inline-flex h-4 w-4 items-center justify-center rounded border bg-white p-0.5 ${
        isVeg ? 'border-veg' : 'border-nonveg'
      } ${className}`}
    >
      <span className={`block h-full w-full rounded-full ${isVeg ? 'bg-veg' : 'bg-nonveg'}`} />
    </span>
  );
}
