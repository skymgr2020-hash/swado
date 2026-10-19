import { useEffect, useMemo, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { api } from '../api/client';
import MenuItemRow from '../components/MenuItemRow';
import { ErrorState } from '../components/Skeletons';
import { useCart } from '../context/CartContext';
import { StarIcon, ClockIcon, ChevronDownIcon, ArrowRightIcon } from '../components/ui/icons';

const SORTS = [
  { key: 'default', label: 'Recommended' },
  { key: 'price_low', label: 'Price: Low to High' },
  { key: 'price_high', label: 'Price: High to Low' },
  { key: 'rating', label: 'Rating' },
];

function categoryAnchor(name) {
  return `category-${name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
}

export default function RestaurantDetail() {
  const { id } = useParams();
  const [searchParams] = useSearchParams();
  const promoId = searchParams.get('promo');
  const highlightItemId = searchParams.get('item');

  const [restaurant, setRestaurant] = useState(null);
  const [menuItems, setMenuItems] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [promo, setPromo] = useState(null);
  const { itemCount, openDrawer, setCouponCode } = useCart();

  const [vegOnly, setVegOnly] = useState(false);
  const [bestsellerOnly, setBestsellerOnly] = useState(false);
  const [sort, setSort] = useState('default');

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    Promise.all([api.get(`/restaurants/${id}`), api.get(`/restaurants/${id}/menu`)])
      .then(([restaurantRes, menuRes]) => {
        if (cancelled) return;
        setRestaurant(restaurantRes?.data ?? null);
        setMenuItems(menuRes?.data?.menu_items ?? []);
        setCategories(menuRes?.data?.categories ?? []);
      })
      .catch((err) => !cancelled && setError(err.message))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, [id]);

  // Arrived via a promo card -- fetch the real promotion so the banner
  // below shows genuine details, and pass any coupon code through to the
  // cart so checkout doesn't make the customer hunt it down (this is the
  // authoritative path: PromoCard also sets it before navigating, but a
  // direct/shared link to this URL wouldn't have gone through that).
  useEffect(() => {
    if (!promoId) {
      setPromo(null);
      return undefined;
    }
    let cancelled = false;
    api
      .get(`/promotions/${promoId}`)
      .then((res) => {
        if (cancelled) return;
        const data = res?.data ?? null;
        setPromo(data);
        const code = data?.coupon_codes?.[0]?.code ?? data?.coupon_codes?.[0];
        if (code) setCouponCode(String(code).toUpperCase());
      })
      .catch(() => !cancelled && setPromo(null));
    return () => {
      cancelled = true;
    };
  }, [promoId, setCouponCode]);

  // Scroll to and highlight the specific dish a promo/dish card pointed at.
  useEffect(() => {
    if (!highlightItemId || loading || !menuItems.length) return;
    const el = document.getElementById(`menu-item-${highlightItemId}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, [highlightItemId, loading, menuItems.length]);

  const allGroups = useMemo(() => {
    const byName = new Map();
    for (const item of menuItems) {
      const key = item.category_name || 'Menu';
      if (!byName.has(key)) byName.set(key, []);
      byName.get(key).push(item);
    }
    const orderedNames = [...categories.map((c) => c.name), ...byName.keys()].filter(
      (name, idx, arr) => arr.indexOf(name) === idx
    );
    return orderedNames.filter((name) => byName.has(name)).map((name) => ({ name, items: byName.get(name) }));
  }, [menuItems, categories]);

  const filteredGroups = useMemo(() => {
    const sortItems = (items) => {
      const list = [...items];
      if (sort === 'price_low') {
        list.sort((a, b) => Number(a.final_price ?? a.price ?? 0) - Number(b.final_price ?? b.price ?? 0));
      } else if (sort === 'price_high') {
        list.sort((a, b) => Number(b.final_price ?? b.price ?? 0) - Number(a.final_price ?? a.price ?? 0));
      } else if (sort === 'rating') {
        list.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
      }
      return list;
    };

    return allGroups
      .map((group) => ({
        name: group.name,
        items: sortItems(
          group.items.filter((item) => (!vegOnly || item.is_veg) && (!bestsellerOnly || item.is_bestseller))
        ),
      }))
      .filter((group) => group.items.length > 0);
  }, [allGroups, vegOnly, bestsellerOnly, sort]);

  const filtersActive = vegOnly || bestsellerOnly;

  function jumpTo(name) {
    document.getElementById(categoryAnchor(name))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  if (loading)
    return <div className="mx-auto max-w-5xl px-4 py-16 text-center text-sm text-ink-soft">Loading restaurant…</div>;
  if (error)
    return (
      <div className="mx-auto max-w-5xl px-4 py-10">
        <ErrorState message={error} />
      </div>
    );
  if (!restaurant) return null;

  return (
    <div className="mx-auto max-w-5xl px-4 py-6">
      {/* Cross-restaurant cart conflicts are resolved app-wide by
          <CartConflictModal /> (mounted in App), so no inline banner here. */}

      {promo && (
        <div className="mb-4 overflow-hidden rounded-2xl bg-gradient-to-r from-brand to-brand-strong p-4 text-sm text-white shadow-brand">
          <p className="font-extrabold tracking-tight">{promo.title}</p>
          {promo.description && <p className="mt-0.5 text-white/90">{promo.description}</p>}
          <p className="mt-1 text-white/90">
            {promo.coupon_codes?.length > 0
              ? `Use code ${promo.coupon_codes[0]?.code ?? promo.coupon_codes[0]} at checkout.`
              : 'Applied automatically at checkout.'}
          </p>
        </div>
      )}

      <div className="overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
        {restaurant.banner_image && (
          <div className="relative">
            <img src={restaurant.banner_image} alt={restaurant.name} className="h-56 w-full object-cover" />
            <div className="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/30 to-transparent" />
          </div>
        )}
        <div className="p-5">
          <h1 className="font-display text-2xl font-extrabold tracking-tight text-ink">{restaurant.name}</h1>
          <p className="mt-1 text-sm text-ink-soft">
            {Array.isArray(restaurant.cuisine) ? restaurant.cuisine.join(', ') : restaurant.cuisine}
          </p>
          <div className="mt-3 flex flex-wrap items-center gap-2.5 text-sm">
            {restaurant.rating != null && (
              <span className="flex items-center gap-1 rounded-lg bg-rating px-2 py-0.5 font-extrabold text-white">
                {Number(restaurant.rating).toFixed(1)}
                <StarIcon size={12} className="text-white" />
              </span>
            )}
            {restaurant.eta_range && (
              <span className="flex items-center gap-1 font-medium text-ink-soft">
                <ClockIcon size={14} className="text-ink-faint" />
                {restaurant.eta_range}
              </span>
            )}
            {restaurant.amount_for_one != null && (
              <span className="font-medium text-ink-soft">₹{restaurant.amount_for_one} for one</span>
            )}
            <span
              className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${
                restaurant.is_open_now
                  ? 'bg-[color-mix(in_oklab,var(--color-veg),#fff_84%)] text-veg'
                  : 'bg-[color-mix(in_oklab,var(--color-danger),#fff_84%)] text-danger'
              }`}
            >
              {restaurant.is_open_now ? 'Open now' : restaurant.next_opening_label || 'Closed'}
            </span>
          </div>
          {restaurant.address && <p className="mt-2 text-xs text-ink-faint">{restaurant.address}</p>}
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr] lg:items-start">
        <aside className="lg:sticky lg:top-24">
          <div className="rounded-2xl border border-line bg-surface p-4 shadow-card lg:mb-4">
            <p className="mb-2.5 text-xs font-bold uppercase tracking-wide text-ink-faint">Filters</p>
            <label className="mb-2 flex items-center gap-2 text-sm font-medium text-ink">
              <input type="checkbox" className="accent-[var(--color-brand)]" checked={vegOnly} onChange={(e) => setVegOnly(e.target.checked)} />
              Veg only
            </label>
            <label className="mb-3 flex items-center gap-2 text-sm font-medium text-ink">
              <input type="checkbox" className="accent-[var(--color-brand)]" checked={bestsellerOnly} onChange={(e) => setBestsellerOnly(e.target.checked)} />
              Bestsellers
            </label>

            <p className="mb-1 text-xs font-bold uppercase tracking-wide text-ink-faint">Sort by</p>
            <div className="relative">
              <select
                value={sort}
                onChange={(e) => setSort(e.target.value)}
                className="field cursor-pointer appearance-none pr-8"
              >
                {SORTS.map((s) => (
                  <option key={s.key} value={s.key}>
                    {s.label}
                  </option>
                ))}
              </select>
              <ChevronDownIcon size={15} className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-soft" />
            </div>

            {filtersActive && (
              <button
                onClick={() => {
                  setVegOnly(false);
                  setBestsellerOnly(false);
                }}
                className="mt-3 text-xs font-bold text-brand"
              >
                Clear filters
              </button>
            )}
          </div>

          {allGroups.length > 1 && (
            <div className="hidden rounded-2xl border border-line bg-surface p-4 shadow-card lg:block">
              <p className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-faint">Categories</p>
              <nav className="flex flex-col gap-1">
                {allGroups.map((group) => (
                  <button
                    key={group.name}
                    onClick={() => jumpTo(group.name)}
                    className="rounded-lg px-2 py-1.5 text-left text-sm font-medium text-ink-soft transition hover:bg-surface-alt hover:text-brand"
                  >
                    {group.name} <span className="text-ink-faint">({group.items.length})</span>
                  </button>
                ))}
              </nav>
            </div>
          )}
        </aside>

        <div className="rounded-2xl border border-line bg-surface p-5 shadow-card">
          {allGroups.length > 1 && (
            <div className="no-scrollbar mb-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">
              {allGroups.map((group) => (
                <button
                  key={group.name}
                  onClick={() => jumpTo(group.name)}
                  className="chip shrink-0 py-1.5 text-xs"
                >
                  {group.name}
                </button>
              ))}
            </div>
          )}

          {filteredGroups.length === 0 && (
            <p className="py-8 text-center text-ink-soft">
              {allGroups.length === 0 ? 'No menu items available right now.' : 'No items match your filters.'}
            </p>
          )}
          {filteredGroups.map((group) => (
            <div key={group.name} id={categoryAnchor(group.name)} className="mb-4 scroll-mt-24 last:mb-0">
              <h3 className="mb-1 font-display text-base font-extrabold tracking-tight text-ink">
                {group.name} <span className="font-semibold text-ink-faint">({group.items.length})</span>
              </h3>
              {group.items.map((item) => (
                <div
                  key={item.id}
                  id={`menu-item-${item.id}`}
                  className={
                    String(item.id) === highlightItemId
                      ? 'scroll-mt-24 rounded-xl ring-2 ring-brand ring-offset-2'
                      : 'scroll-mt-24'
                  }
                >
                  <MenuItemRow item={item} restaurant={restaurant} />
                </div>
              ))}
            </div>
          ))}
        </div>
      </div>

      {restaurant.review_highlights?.length > 0 && (
        <div className="mt-6 rounded-2xl border border-line bg-surface p-5 shadow-card">
          <h3 className="mb-3 font-display text-base font-extrabold tracking-tight text-ink">What people are saying</h3>
          <div className="space-y-3">
            {restaurant.review_highlights.slice(0, 6).map((r) => (
              <div key={r.id} className="border-b border-line pb-3 last:border-none">
                <p className="flex items-center gap-1 text-sm font-bold text-ink">
                  {r.user_name}
                  <span className="flex items-center gap-0.5 text-xs font-semibold text-rating">
                    · {r.rating}
                    <StarIcon size={10} className="text-rating" />
                  </span>
                </p>
                <p className="text-sm text-ink-soft">{r.comment}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {itemCount > 0 && (
        <button
          onClick={openDrawer}
          className="mt-6 flex w-full items-center justify-center gap-1.5 text-sm font-bold text-brand"
        >
          View cart ({itemCount})
          <ArrowRightIcon size={15} />
        </button>
      )}
    </div>
  );
}
