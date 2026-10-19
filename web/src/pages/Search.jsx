import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, friendlyError } from '../api/client';
import { useUserLocation } from '../context/LocationContext';
import { useDebounce } from '../hooks/useDebounce';
import RestaurantCard from '../components/RestaurantCard';
import DishCard from '../components/DishCard';
import SearchAutocomplete from '../components/SearchAutocomplete';
import { RestaurantGridSkeleton, EmptyState, ErrorState } from '../components/Skeletons';

const MIN_QUERY_LENGTH = 2;

export default function Search() {
  const { location } = useUserLocation();
  const [params, setParams] = useSearchParams();
  const [query, setQuery] = useState(params.get('q') || '');
  const debouncedQuery = useDebounce(query, 350);
  const [restaurants, setRestaurants] = useState([]);
  const [foods, setFoods] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    setParams(debouncedQuery ? { q: debouncedQuery } : {}, { replace: true });
  }, [debouncedQuery]);

  useEffect(() => {
    const term = debouncedQuery.trim();
    if (term.length < MIN_QUERY_LENGTH) {
      setRestaurants([]);
      setFoods([]);
      setLoading(false);
      setError(null);
      return undefined;
    }

    let cancelled = false;
    setLoading(true);
    setError(null);

    const hasLoc = location?.lat != null && location?.lng != null;
    const searchParams = new URLSearchParams({ keyword: term, limit: '30' });
    if (hasLoc) {
      searchParams.set('lat', String(location.lat));
      searchParams.set('lng', String(location.lng));
    }

    // /v1/search covers dish matches (and restaurants); /restaurants/nearby
    // returns the fully-hydrated restaurant cards (logo, banner, rating, ETA)
    // that /v1/search's index rows don't carry. Prefer the latter for the
    // Restaurants section when we have a location to scope it to.
    const nearbyReq = hasLoc
      ? api
          .get(
            `/restaurants/nearby?${new URLSearchParams({
              lat: String(location.lat),
              lng: String(location.lng),
              query: term,
            }).toString()}`,
          )
          .catch(() => null)
      : Promise.resolve(null);

    Promise.all([api.get(`/v1/search?${searchParams.toString()}`).catch((err) => err), nearbyReq])
      .then(([searchRes, nearbyRes]) => {
        if (cancelled) return;
        if (searchRes instanceof Error) {
          setError(friendlyError(searchRes, "We couldn't run that search. Please try again."));
          return;
        }
        const data = searchRes?.data ?? {};
        const nearby = Array.isArray(nearbyRes?.data) ? nearbyRes.data : null;
        setFoods(Array.isArray(data.foods) ? data.foods : []);
        setRestaurants(nearby ?? (Array.isArray(data.restaurants) ? data.restaurants : []));
      })
      .finally(() => !cancelled && setLoading(false));

    return () => {
      cancelled = true;
    };
  }, [debouncedQuery, location?.lat, location?.lng]);

  const term = debouncedQuery.trim();
  const showResults = term.length >= MIN_QUERY_LENGTH;
  const hasResults = restaurants.length > 0 || foods.length > 0;

  return (
    <div className="mx-auto max-w-6xl px-4 py-6">
      <SearchAutocomplete
        autoFocus
        value={query}
        onChange={setQuery}
        placeholder="Search restaurants, cuisines, dishes..."
        className="mb-6 w-full max-w-xl"
        inputClassName="w-full rounded-full border border-line-strong bg-surface px-5 py-3 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-brand focus:shadow-[0_0_0_3px_var(--color-brand-tint-strong)]"
      />

      {!showResults && (
        <EmptyState title="Start typing to search" subtitle="Find restaurants and dishes near you." />
      )}
      {showResults && loading && <RestaurantGridSkeleton count={8} />}
      {showResults && !loading && error && <ErrorState message={error} />}
      {showResults && !loading && !error && !hasResults && (
        <EmptyState title={`No results for "${term}"`} subtitle="Try a different search term." />
      )}

      {showResults && !loading && !error && hasResults && (
        <div className="space-y-8">
          {foods.length > 0 && (
            <section>
              <h2 className="mb-1 font-display text-lg font-extrabold tracking-tight text-ink">Dishes</h2>
              <p className="mb-3 text-xs font-semibold text-ink-faint">
                {foods.length} {foods.length === 1 ? 'result' : 'results'} for &ldquo;{term}&rdquo;
              </p>
              <div className="flex flex-wrap gap-4">
                {foods.map((food) => (
                  <DishCard key={`d-${food.restaurant_id}-${food.id}`} item={food} />
                ))}
              </div>
            </section>
          )}

          {restaurants.length > 0 && (
            <section>
              <h2 className="mb-1 font-display text-lg font-extrabold tracking-tight text-ink">Restaurants</h2>
              <p className="mb-3 text-xs font-semibold text-ink-faint">
                {restaurants.length} {restaurants.length === 1 ? 'result' : 'results'} for &ldquo;{term}&rdquo;
              </p>
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {restaurants.map((restaurant) => (
                  <RestaurantCard key={restaurant.id} restaurant={restaurant} />
                ))}
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
