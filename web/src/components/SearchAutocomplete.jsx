import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api/client';
import { useUserLocation } from '../context/LocationContext';
import { useDebounce } from '../hooks/useDebounce';
import { SearchIcon, StoreIcon, BagIcon, StarIcon } from './ui/icons';

const EMPTY_RESULTS = { restaurants: [], foods: [], trending: [], corrected: '' };
const MIN_QUERY_LENGTH = 2;

export default function SearchAutocomplete({
  value,
  onChange,
  placeholder = 'Search restaurants, cuisines, dishes...',
  autoFocus = false,
  className = '',
  inputClassName = '',
  adornment = null,
}) {
  const navigate = useNavigate();
  const { location } = useUserLocation();
  const wrapperRef = useRef(null);
  const debouncedQuery = useDebounce(value.trim(), 300);
  const [results, setResults] = useState(EMPTY_RESULTS);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    function onDocumentMouseDown(event) {
      if (!wrapperRef.current?.contains(event.target)) setOpen(false);
    }
    document.addEventListener('mousedown', onDocumentMouseDown);
    return () => document.removeEventListener('mousedown', onDocumentMouseDown);
  }, []);

  useEffect(() => {
    if (!open) return undefined;

    let cancelled = false;
    const hasQuery = debouncedQuery.length >= MIN_QUERY_LENGTH;
    const hasLoc = location?.lat != null && location?.lng != null;

    const params = new URLSearchParams({ limit: '8' });
    if (hasQuery) params.set('keyword', debouncedQuery);
    if (hasLoc) {
      params.set('lat', String(location.lat));
      params.set('lng', String(location.lng));
    }

    // Same split as the /search page: dish matches + trending come from
    // /v1/search, but the restaurant rows want the hydrated card (logo,
    // banner, rating) that only /restaurants/nearby returns.
    const nearbyReq =
      hasQuery && hasLoc
        ? api
            .get(
              `/restaurants/nearby?${new URLSearchParams({
                lat: String(location.lat),
                lng: String(location.lng),
                query: debouncedQuery,
              }).toString()}`,
            )
            .catch(() => null)
        : Promise.resolve(null);

    setLoading(true);
    Promise.all([api.get(`/v1/search?${params.toString()}`).catch(() => null), nearbyReq])
      .then(([searchRes, nearbyRes]) => {
        if (cancelled) return;
        const data = searchRes?.data ?? {};
        const nearby = Array.isArray(nearbyRes?.data) ? nearbyRes.data : null;
        setResults({
          restaurants: nearby ?? (Array.isArray(data.restaurants) ? data.restaurants : []),
          foods: Array.isArray(data.foods) ? data.foods : [],
          trending: Array.isArray(data.trending) ? data.trending : [],
          corrected: data.corrected_keyword || '',
        });
      })
      .catch(() => {
        if (!cancelled) setResults(EMPTY_RESULTS);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [debouncedQuery, open, location?.lat, location?.lng]);

  function submitSearch(nextValue = value) {
    const term = nextValue.trim();
    if (!term) return;
    setOpen(false);
    navigate(`/search?q=${encodeURIComponent(term)}`);
  }

  function go(path) {
    setOpen(false);
    navigate(path);
  }

  function onKeyDown(event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      submitSearch();
    } else if (event.key === 'Escape') {
      setOpen(false);
    }
  }

  const hasQuery = debouncedQuery.length >= MIN_QUERY_LENGTH;
  const foods = results.foods.slice(0, 5);
  const restaurants = results.restaurants.slice(0, foods.length > 0 ? 2 : 5);
  const hasResults = restaurants.length > 0 || foods.length > 0;

  return (
    <form
      ref={wrapperRef}
      onSubmit={(event) => {
        event.preventDefault();
        submitSearch();
      }}
      className={`relative ${className}`}
    >
      {adornment}
      <input
        autoFocus={autoFocus}
        value={value}
        onChange={(event) => {
          onChange(event.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={onKeyDown}
        type="search"
        placeholder={placeholder}
        className={inputClassName}
        autoComplete="off"
        role="combobox"
        aria-expanded={open && (hasResults || hasQuery)}
        aria-autocomplete="list"
      />

      {open && (
        <div className="absolute left-0 top-full z-40 mt-2 max-h-[70vh] w-[min(92vw,440px)] overflow-y-auto rounded-2xl border border-line bg-surface p-2 shadow-pop">
          {loading && !hasResults ? (
            <div className="space-y-2 p-1">
              {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="h-12 animate-pulse rounded-lg bg-surface-alt" />
              ))}
            </div>
          ) : (
            <>
              {!hasQuery && !hasResults && results.trending.length > 0 && (
                <div className="p-1">
                  <p className="px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-ink-faint">Trending</p>
                  <div className="flex flex-wrap gap-2 px-1 pt-1">
                    {results.trending.slice(0, 8).map((term) => (
                      <button
                        key={term}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => {
                          onChange(term);
                          submitSearch(term);
                        }}
                        className="rounded-full border border-line bg-surface-alt px-3 py-1.5 text-xs font-semibold text-ink-soft transition hover:border-brand hover:text-brand"
                      >
                        {term}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {foods.length > 0 && (
                <div className="flex flex-col">
                  <p className="px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-ink-faint">Products</p>
                  {foods.map((food) => {
                    const image = food.image_url || food.images?.[0] || null;
                    const price = food.discounted_price ?? food.price;
                    return (
                      <button
                        key={`d-${food.restaurant_id}-${food.id}`}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => go(`/restaurants/${food.restaurant_id}?item=${food.id}`)}
                        className="flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-surface-alt"
                      >
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-alt">
                          {image ? (
                            <img src={image} alt="" className="h-full w-full object-cover" loading="lazy" />
                          ) : (
                            <BagIcon size={15} className="text-ink-faint" />
                          )}
                        </span>
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-sm font-bold text-ink">{food.name || food.title}</span>
                          <span className="block truncate text-xs font-medium text-ink-soft">
                            {food.restaurant_name || food.category_name || 'Product'}
                          </span>
                        </span>
                        {price != null && (
                          <span className="shrink-0 text-xs font-black text-ink">₹{Number(price).toFixed(0)}</span>
                        )}
                      </button>
                    );
                  })}
                </div>
              )}

              {restaurants.length > 0 && (
                <div className="flex flex-col">
                  <p className="px-2 py-1 pt-2 text-[10px] font-extrabold uppercase tracking-wide text-ink-faint">
                    Restaurants
                  </p>
                  {restaurants.map((restaurant) => {
                    const image = restaurant.logo_image || restaurant.banner_image || null;
                    const name = restaurant.name || restaurant.title || 'Restaurant';
                    const cuisineList = Array.isArray(restaurant.cuisine)
                      ? restaurant.cuisine.join(', ')
                      : restaurant.cuisine || restaurant.cuisine_text || '';
                    const rating = Number(restaurant.rating);
                    return (
                      <button
                        key={`r-${restaurant.id}`}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => go(`/restaurants/${restaurant.id}`)}
                        className="flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-surface-alt"
                      >
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-alt">
                          {image ? (
                            <img src={image} alt="" className="h-full w-full object-cover" loading="lazy" />
                          ) : (
                            <StoreIcon size={15} className="text-ink-faint" />
                          )}
                        </span>
                        <span className="flex min-w-0 flex-1 flex-col">
                          <span className="flex min-w-0 items-center gap-1.5">
                            <span className="min-w-0 flex-1 truncate text-sm font-bold text-ink">{name}</span>
                            {Number.isFinite(rating) && rating > 0 && (
                              <span className="flex shrink-0 items-center gap-0.5 text-xs font-bold text-ink-soft">
                                <StarIcon size={10} className="text-rating" />
                                {rating.toFixed(1)}
                              </span>
                            )}
                          </span>
                          {cuisineList && (
                            <span className="block truncate text-xs font-medium text-ink-soft">{cuisineList}</span>
                          )}
                        </span>
                      </button>
                    );
                  })}
                </div>
              )}

              {value.trim() && (hasResults || hasQuery) && (
                <button
                  type="button"
                  onMouseDown={(event) => event.preventDefault()}
                  onClick={() => submitSearch()}
                  className="mt-1 flex w-full items-center justify-center gap-2 rounded-lg px-2 py-2 text-center text-xs font-extrabold text-brand transition hover:bg-surface-alt"
                >
                  <SearchIcon size={13} />
                  View all results for &ldquo;{value.trim()}&rdquo;
                </button>
              )}

              {!loading && hasQuery && !hasResults && (
                <p className="px-3 py-6 text-center text-[13px] font-bold text-ink-soft">
                  No matches for &ldquo;{debouncedQuery}&rdquo;
                </p>
              )}
            </>
          )}
        </div>
      )}
    </form>
  );
}
