import { useCart } from '../context/CartContext';

// A cart holds items from a single restaurant at a time. When someone adds a
// dish from a different one (from the home rails, a restaurant page, search…)
// addItem() parks the request in `conflict` instead of mutating the cart —
// this modal is the one place, app-wide, that resolves it.
export default function CartConflictModal() {
  const { conflict, confirmSwap, dismissConflict, cart } = useCart();
  if (!conflict) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
      onClick={(e) => e.target === e.currentTarget && dismissConflict()}
    >
      <div className="w-full max-w-sm rounded-3xl border border-line bg-surface p-6 shadow-pop">
        <h2 className="font-display text-lg font-extrabold tracking-tight text-ink">Start a new cart?</h2>
        <p className="mt-2 text-sm text-ink-soft">
          Your cart has items from <b className="text-ink">{cart.restaurantName}</b>. Adding{' '}
          <b className="text-ink">{conflict.item.name}</b> from{' '}
          <b className="text-ink">{conflict.restaurant.name}</b> will clear the current cart.
        </p>
        <div className="mt-5 flex gap-2">
          <button onClick={confirmSwap} className="btn btn-primary flex-1">
            Clear &amp; add
          </button>
          <button onClick={dismissConflict} className="btn btn-outline flex-1">
            Keep cart
          </button>
        </div>
      </div>
    </div>
  );
}
