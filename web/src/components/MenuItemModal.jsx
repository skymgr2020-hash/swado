import { useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { useCart, cartLineKey, cartUnitPrice } from '../context/CartContext';
import { StarIcon, CloseIcon, PlusIcon, MinusIcon, ClockIcon, CheckIcon } from './ui/icons';

function DietMark({ item }) {
  const type = item.food_type || (item.is_veg === false ? 'non_veg' : item.is_veg ? 'veg' : null);
  if (!type) return null;
  const color = type === 'non_veg' ? 'border-nonveg' : type === 'egg' ? 'border-gold' : 'border-veg';
  const dot = type === 'non_veg' ? 'bg-nonveg' : type === 'egg' ? 'bg-gold' : 'bg-veg';
  return (
    <span className={`inline-flex h-4 w-4 items-center justify-center border ${color} p-0.5`} style={{ borderRadius: 3 }}>
      <span className={`block h-full w-full rounded-full ${dot}`} />
    </span>
  );
}

function Badge({ children, tone = 'muted' }) {
  const cls =
    tone === 'gold'
      ? 'bg-gold-tint text-gold'
      : tone === 'brand'
        ? 'bg-brand-tint text-brand-strong'
        : 'bg-surface-alt text-ink-soft';
  return (
    <span className={`rounded-full px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wide ${cls}`}>
      {children}
    </span>
  );
}

export default function MenuItemModal({ item, restaurant, onClose, closed = false }) {
  const { cart, addItem, updateQuantity } = useCart();

  useEffect(() => {
    function onKey(e) {
      if (e.key === 'Escape') onClose();
    }
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [onClose]);

  const variants = useMemo(
    () => (Array.isArray(item.variants) ? item.variants : []).filter((v) => v && v.name),
    [item.variants]
  );
  const addOns = useMemo(
    () => (Array.isArray(item.add_ons ?? item.addons) ? item.add_ons ?? item.addons : []).filter((a) => a && a.name),
    [item.add_ons, item.addons]
  );

  const [variant, setVariant] = useState(
    () => variants.find((v) => v.is_available !== false) || variants[0] || null
  );
  const [picked, setPicked] = useState(() => new Set());
  const selectedAddOns = addOns.filter((a) => picked.has(a.name));

  const menuItemId = item.menu_item_id ?? item.id;
  const rest = restaurant ?? { id: item.restaurant_id, name: item.restaurant_name };
  const available = item.is_available !== false;
  const canAdd = available && !closed && menuItemId != null && rest.id != null;

  const basePrice = cartUnitPrice(item, null, []);
  const unitPrice = cartUnitPrice(item, variant, selectedAddOns);
  const baseOriginal = Number(item.price ?? 0);
  const hasDiscount = !variant && (item.discounted_price != null || item.final_price != null) && baseOriginal > unitPrice;

  const lineId = cartLineKey(menuItemId, variant, selectedAddOns);
  const inCart = cart.items.find((i) => i.id === lineId);
  const qty = inCart?.quantity ?? 0;

  const images = (Array.isArray(item.images) && item.images.length
    ? item.images
    : [item.image_url || item.image].filter(Boolean)
  ).slice(0, 5);
  const [activeImage, setActiveImage] = useState(0);
  const hero = images[activeImage] || images[0];

  function toggleAddOn(name) {
    setPicked((prev) => {
      const next = new Set(prev);
      if (next.has(name)) next.delete(name);
      else next.add(name);
      return next;
    });
  }

  function add() {
    addItem(rest, item, 1, { variant, addOns: selectedAddOns });
  }

  const prepTime = item.preparation_time ?? item.prep_time;
  const meta = [item.category_name, item.cuisine_name].filter(Boolean).join(' · ');
  const tags = Array.isArray(item.tags) ? item.tags.filter(Boolean) : [];

  return createPortal(
    <div
      className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
      onClick={(e) => e.target === e.currentTarget && onClose()}
    >
      <div className="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-pop sm:rounded-3xl">
        <div className="relative shrink-0">
          {hero ? (
            <img src={hero} alt={item.name} className="h-52 w-full object-cover" />
          ) : (
            <div className="flex h-20 w-full items-center justify-center bg-surface-alt text-xs text-ink-faint">
              No image
            </div>
          )}
          <button
            onClick={onClose}
            className="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-surface/90 text-ink shadow-card backdrop-blur-sm transition hover:bg-surface"
            aria-label="Close"
          >
            <CloseIcon size={18} />
          </button>
          {images.length > 1 && (
            <div className="absolute inset-x-0 bottom-2 flex justify-center gap-1.5">
              {images.map((src, i) => (
                <button
                  key={src + i}
                  onClick={() => setActiveImage(i)}
                  aria-label={`Image ${i + 1}`}
                  className={`h-1.5 rounded-full transition-all ${i === activeImage ? 'w-5 bg-white' : 'w-1.5 bg-white/60'}`}
                />
              ))}
            </div>
          )}
        </div>

        <div className="flex-1 overflow-y-auto p-5">
          <div className="flex flex-wrap items-center gap-1.5">
            <DietMark item={item} />
            {item.diet_label && <Badge>{item.diet_label}</Badge>}
            {item.is_bestseller && <Badge tone="gold">★ Bestseller</Badge>}
            {item.is_recommended && <Badge tone="brand">Must try</Badge>}
            {item.is_new && <Badge tone="brand">New</Badge>}
            {item.is_spicy && <Badge>🌶 Spicy</Badge>}
          </div>

          <h2 className="mt-2 font-display text-xl font-extrabold tracking-tight text-ink">{item.name}</h2>

          <div className="mt-1.5 flex items-center gap-2">
            <span className="text-base font-extrabold text-ink">₹{unitPrice.toFixed(0)}</span>
            {hasDiscount && <span className="text-sm text-ink-faint line-through">₹{baseOriginal.toFixed(0)}</span>}
          </div>

          <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-ink-soft">
            {item.rating != null && Number(item.rating) > 0 && (
              <span className="flex items-center gap-1">
                <StarIcon size={12} className="text-rating" />
                {Number(item.rating).toFixed(1)}
                {item.total_orders ? <span className="font-normal text-ink-faint"> ({item.total_orders} orders)</span> : null}
              </span>
            )}
            {prepTime ? (
              <span className="flex items-center gap-1">
                <ClockIcon size={12} className="text-ink-faint" />~{prepTime} min
              </span>
            ) : null}
          </div>

          {meta && <p className="mt-1 text-xs text-ink-faint">{meta}</p>}
          {(item.restaurant_name || rest.name) && !restaurant && (
            <p className="mt-1 text-xs text-ink-soft">{item.restaurant_name || rest.name}</p>
          )}

          {item.description && <p className="mt-3 text-sm leading-6 text-ink-soft">{item.description}</p>}

          {tags.length > 0 && (
            <div className="mt-3 flex flex-wrap gap-1.5">
              {tags.map((t) => (
                <span key={t} className="rounded-full bg-surface-alt px-2.5 py-1 text-[11px] font-semibold text-ink-soft">
                  {t}
                </span>
              ))}
            </div>
          )}

          {variants.length > 0 && (
            <fieldset className="mt-5">
              <legend className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-faint">Choose a size</legend>
              <div className="space-y-2">
                {variants.map((v) => {
                  const on = variant?.name === v.name;
                  const off = v.is_available === false;
                  return (
                    <button
                      key={v.name}
                      type="button"
                      disabled={off}
                      onClick={() => setVariant(v)}
                      className={`flex w-full items-center justify-between gap-3 rounded-xl border p-3 text-left text-sm transition ${
                        on ? 'border-brand bg-brand-tint' : 'border-line-strong bg-surface hover:border-brand'
                      } ${off ? 'cursor-not-allowed opacity-50' : ''}`}
                    >
                      <span className="flex items-center gap-2 font-semibold text-ink">
                        <span className={`flex h-4 w-4 items-center justify-center rounded-full border-2 ${on ? 'border-brand bg-brand text-white' : 'border-line-strong'}`}>
                          {on && <CheckIcon size={10} />}
                        </span>
                        {v.name}
                      </span>
                      <span className="font-bold text-ink">₹{(basePrice + Number(v.price ?? 0)).toFixed(0)}</span>
                    </button>
                  );
                })}
              </div>
            </fieldset>
          )}

          {addOns.length > 0 && (
            <fieldset className="mt-5">
              <legend className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-faint">Add-ons</legend>
              <div className="space-y-2">
                {addOns.map((a) => {
                  const on = picked.has(a.name);
                  const off = a.is_available === false;
                  return (
                    <button
                      key={a.name}
                      type="button"
                      disabled={off}
                      onClick={() => toggleAddOn(a.name)}
                      className={`flex w-full items-center justify-between gap-3 rounded-xl border p-3 text-left text-sm transition ${
                        on ? 'border-brand bg-brand-tint' : 'border-line-strong bg-surface hover:border-brand'
                      } ${off ? 'cursor-not-allowed opacity-50' : ''}`}
                    >
                      <span className="flex items-center gap-2 font-semibold text-ink">
                        <span className={`flex h-4 w-4 items-center justify-center rounded border-2 ${on ? 'border-brand bg-brand text-white' : 'border-line-strong'}`}>
                          {on && <CheckIcon size={10} />}
                        </span>
                        {a.name}
                      </span>
                      <span className="font-bold text-ink-soft">+₹{Number(a.price ?? 0).toFixed(0)}</span>
                    </button>
                  );
                })}
              </div>
            </fieldset>
          )}

          {closed && <p className="mt-4 text-sm font-bold text-danger">This restaurant is closed right now.</p>}
          {!closed && !available && <p className="mt-4 text-sm font-bold text-danger">Currently unavailable</p>}
        </div>

        <div className="shrink-0 border-t border-line bg-surface px-5 py-3.5">
          {closed ? (
            <button disabled className="btn btn-primary w-full py-3">
              Restaurant closed
            </button>
          ) : qty === 0 ? (
            <button onClick={add} disabled={!canAdd} className="btn btn-primary w-full py-3">
              Add to cart · ₹{unitPrice.toFixed(0)}
            </button>
          ) : (
            <div className="flex items-center justify-between gap-3">
              <div className="stepper text-base">
                <button onClick={() => updateQuantity(lineId, qty - 1)} aria-label="Decrease quantity">
                  <MinusIcon size={16} />
                </button>
                <span className="min-w-6 text-center text-sm font-extrabold">{qty}</span>
                <button onClick={add} aria-label="Increase quantity">
                  <PlusIcon size={16} />
                </button>
              </div>
              <span className="text-sm font-extrabold text-ink">₹{(unitPrice * qty).toFixed(0)}</span>
              <button onClick={onClose} className="btn btn-outline px-4 py-2.5 text-sm">
                Done
              </button>
            </div>
          )}
        </div>
      </div>
    </div>,
    document.body
  );
}
