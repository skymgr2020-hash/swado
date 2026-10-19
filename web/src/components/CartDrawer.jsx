import { useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { EmptyState } from './Skeletons';
import { CloseIcon, PlusIcon, MinusIcon, ArrowRightIcon } from './ui/icons';

// The drawer is step 1 of the order flow (cart review). Checkout requires a
// login, so a logged-out "Proceed to checkout" opens the login modal first and
// continues to /checkout once the customer is signed in.
export default function CartDrawer() {
  const { cart, updateQuantity, subtotal, isDrawerOpen, closeDrawer } = useCart();
  const { isAuthed, openLogin, isLoginOpen } = useAuth();
  const navigate = useNavigate();
  const pendingRef = useRef(false);

  function goToCheckout() {
    if (!isAuthed) {
      pendingRef.current = true;
      closeDrawer();
      openLogin();
      return;
    }
    closeDrawer();
    navigate('/checkout');
  }

  useEffect(() => {
    if (isAuthed && pendingRef.current) {
      pendingRef.current = false;
      navigate('/checkout');
    }
  }, [isAuthed, navigate]);

  // Customer dismissed the login modal without signing in — drop the intent.
  useEffect(() => {
    if (pendingRef.current && !isLoginOpen && !isAuthed) pendingRef.current = false;
  }, [isLoginOpen, isAuthed]);

  return (
    <>
      <div
        className={`fixed inset-0 z-40 bg-black/50 transition-opacity duration-300 ${
          isDrawerOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
        }`}
        onClick={closeDrawer}
      />
      <aside
        className={`fixed inset-y-0 right-0 z-40 flex w-full max-w-md transform flex-col bg-canvas shadow-pop transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] ${
          isDrawerOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        <div className="flex items-center justify-between border-b border-line bg-surface px-5 py-4">
          <h2 className="font-display text-lg font-extrabold tracking-tight text-ink">Your cart</h2>
          <button
            onClick={closeDrawer}
            className="flex h-9 w-9 items-center justify-center rounded-full text-ink-soft transition hover:bg-surface-alt"
            aria-label="Close cart"
          >
            <CloseIcon size={18} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-5 py-4">
          {!cart.items.length ? (
            <EmptyState title="Your cart is empty" subtitle="Add items from a restaurant to see them here." />
          ) : (
            <>
              <p className="mb-3 text-sm font-semibold text-ink-soft">from {cart.restaurantName}</p>
              <div className="rounded-2xl border border-line bg-surface-alt p-3.5">
                {cart.items.map((item) => (
                  <div
                    key={item.id}
                    className="flex items-center justify-between gap-3 border-b border-line py-3 first:pt-0 last:border-none last:pb-0"
                  >
                    <div className="min-w-0 flex-1">
                      <p className="line-clamp-1 text-sm font-bold text-ink">{item.name}</p>
                      <p className="text-xs text-ink-soft">₹{item.price.toFixed(0)}</p>
                    </div>
                    <div className="stepper text-sm">
                      <button onClick={() => updateQuantity(item.id, item.quantity - 1)} aria-label="Decrease quantity">
                        <MinusIcon size={15} />
                      </button>
                      <span className="text-sm font-extrabold">{item.quantity}</span>
                      <button onClick={() => updateQuantity(item.id, item.quantity + 1)} aria-label="Increase quantity">
                        <PlusIcon size={15} />
                      </button>
                    </div>
                    <span className="w-16 shrink-0 text-right text-sm font-extrabold text-ink">
                      ₹{(item.price * item.quantity).toFixed(0)}
                    </span>
                  </div>
                ))}
              </div>

              <div className="mt-4 flex items-center justify-between rounded-2xl border border-line bg-surface-alt p-3.5 text-sm">
                <span className="font-semibold text-ink-soft">Subtotal</span>
                <span className="text-base font-extrabold text-ink">₹{subtotal.toFixed(0)}</span>
              </div>
              <p className="mt-2 text-xs text-ink-faint">
                Delivery fee, taxes and offers are calculated at checkout.
              </p>
            </>
          )}
        </div>

        {cart.items.length > 0 && (
          <div className="border-t border-line bg-surface px-5 py-4">
            <button onClick={goToCheckout} className="btn btn-primary w-full py-3">
              Proceed to checkout
              <ArrowRightIcon size={16} />
            </button>
          </div>
        )}
      </aside>
    </>
  );
}
