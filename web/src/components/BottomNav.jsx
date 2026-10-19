import { Link, useLocation } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { HomeIcon, SearchIcon, CartIcon, ReceiptIcon } from './ui/icons';

// Mobile-only bottom navigation (mirrors the Flutter home bottom bar).
// Hidden on the focused checkout / order flows so it doesn't fight their
// sticky action bars.
const HIDDEN_ON = [/^\/checkout/, /^\/order\//];

export default function BottomNav() {
  const { pathname } = useLocation();
  const { itemCount, openDrawer } = useCart();

  if (HIDDEN_ON.some((re) => re.test(pathname))) return null;

  const isHome = pathname === '/';
  const isSearch = pathname.startsWith('/search');
  const isOrders = pathname.startsWith('/orders');

  const itemCls = (active) =>
    `flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold transition ${
      active ? 'text-brand' : 'text-ink-soft'
    }`;

  return (
    <nav
      className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur-md sm:hidden"
      style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
      aria-label="Primary"
    >
      <div className="mx-auto flex max-w-md items-stretch">
        <Link to="/" className={itemCls(isHome)}>
          <HomeIcon size={22} />
          Home
        </Link>
        <Link to="/search" className={itemCls(isSearch)}>
          <SearchIcon size={22} />
          Search
        </Link>
        <button type="button" onClick={openDrawer} className={`${itemCls(false)} relative`}>
          <span className="relative">
            <CartIcon size={22} />
            {itemCount > 0 && (
              <span className="absolute -right-2 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-extrabold text-white">
                {itemCount}
              </span>
            )}
          </span>
          Cart
        </button>
        <Link to="/orders" className={itemCls(isOrders)}>
          <ReceiptIcon size={22} />
          Orders
        </Link>
      </div>
    </nav>
  );
}
