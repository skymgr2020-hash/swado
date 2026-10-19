import { useEffect, useMemo, useState } from 'react';
import Rail from "../components/ui/Rail";
import { api } from '../api/client';
import { useUserLocation } from '../context/LocationContext';
import RestaurantCard from '../components/RestaurantCard';
import CategoryStrip from '../components/CategoryStrip';
import PromoStrip from '../components/PromoStrip';
import GlobalPromoBanners from '../components/GlobalPromoBanners';
import DishStrip from '../components/DishStrip';
import BrandStrip from '../components/BrandStrip';
import BannerCarousel from '../components/BannerCarousel';
import HeroBanner from '../components/HeroBanner';
import GenericSectionStrip from '../components/GenericSectionStrip';
import { RestaurantGridSkeleton, EmptyState, ErrorState } from '../components/Skeletons';
import { ChevronDownIcon } from '../components/ui/icons';

const SORTS = [
  { key: 'relevance', label: 'Relevance' },
  { key: 'rating', label: 'Rating' },
  { key: 'delivery_time', label: 'Delivery time' },
  { key: 'cost_low', label: 'Cost: Low to High' },
];

// Every restaurant-shaped section type HomeSectionService::resolveDynamicSection
// can emit -- all carry full restaurant objects (or, for the two "discovery"
// tokens, an empty items:[] placement marker -- see client-feed handling below).
const RESTAURANT_TYPES = new Set([
  'restaurant_discovery',
  'restaurant_grid',
  'featured_restaurants',
  'recommended_for_you',
  'popular_restaurants',
  'new_arrivals',
  'trending_near_you',
  'nearby_restaurants',
]);
const CATEGORY_TYPES = new Set(['categories', 'cuisine_grid']);
const PROMO_TYPES = new Set(['deals_for_you', 'promotion_type_section']);
const TOP_BANNER_TYPES = new Set(['banner_carousel', 'hero_banner']);

// A promo/coupon that targets no specific restaurant, dish, category or
// cuisine has nowhere for a click to take the customer -- e.g. sitewide
// free delivery, a blanket first-order %-off. Those render as a plain
// banner instead of a click-through deal card.
function isGlobalPromo(deal) {
  const targets = deal.targets || {};
  const hasScope =
    deal.restaurant_id ||
    targets.restaurant_ids?.length > 0 ||
    targets.item_ids?.length > 0 ||
    targets.category_ids?.length > 0 ||
    targets.cuisine_ids?.length > 0;
  return !hasScope;
}

export default function Home() {
  const { location, openPicker } = useUserLocation();
  // Full ordered list from GET /api/home/sections - Home Section Management
  // is entirely admin-configurable (order, which tokens exist), so every
  // entry is rendered generically by `type` rather than picking two by name.
  const [sections, setSections] = useState([]);
  const [restaurants, setRestaurants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cuisine, setCuisine] = useState(null);
  const [openNow, setOpenNow] = useState(false);
  const [sort, setSort] = useState('relevance');
  const [maxPrice, setMaxPrice] = useState(null);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    if (!location) {
      setSections([]);
      setRestaurants([]);
      setError(null);
      setLoading(false);
      return undefined;
    }

    let cancelled = false;
    setLoading(true);
    setError(null);

    const params = new URLSearchParams({
      lat: String(location.lat),
      lng: String(location.lng),
      platform: 'web',
    });
    const restaurantParams = new URLSearchParams({
      lat: String(location.lat),
      lng: String(location.lng),
    });
    if (openNow) restaurantParams.set('open_now', '1');

    Promise.allSettled([
      api.get(`/home/sections?${params.toString()}`),
      api.get(`/restaurants/nearby?${restaurantParams.toString()}`),
    ])
      .then(([sectionsResult, restaurantsResult]) => {
        if (cancelled) return;

        setSections(sectionsResult.status === 'fulfilled' ? sectionsResult.value?.data ?? [] : []);
        setRestaurants(restaurantsResult.status === 'fulfilled' ? restaurantsResult.value?.data ?? [] : []);

        if (sectionsResult.status === 'rejected' && restaurantsResult.status === 'rejected') {
          setError(restaurantsResult.reason?.message || sectionsResult.reason?.message || 'Unable to load home data.');
        }
      })
      .finally(() => !cancelled && setLoading(false));

    return () => {
      cancelled = true;
    };
  }, [location?.lat, location?.lng, openNow, reloadKey]);

  const visibleRestaurants = useMemo(() => {
    let list = restaurants;
    if (cuisine) {
      list = list.filter((r) => (r.cuisine || []).some((c) => c.toLowerCase() === cuisine.toLowerCase()));
    }
    if (maxPrice != null) {
      list = list.filter((r) => r.amount_for_one == null || r.amount_for_one <= maxPrice);
    }
    list = [...list];
    if (sort === 'rating') {
      list.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
    } else if (sort === 'delivery_time') {
      list.sort((a, b) => (a.eta_minutes ?? 999) - (b.eta_minutes ?? 999));
    } else if (sort === 'cost_low') {
      list.sort((a, b) => (a.amount_for_one ?? 0) - (b.amount_for_one ?? 0));
    }
    return list;
  }, [restaurants, cuisine, maxPrice, sort]);

  const orderedSections = useMemo(() => {
    const enabledSections = sections.filter((section) => section && section.enabled !== false);
    const topBanners = enabledSections.filter((section) => TOP_BANNER_TYPES.has(section.type));
    const remainingSections = enabledSections.filter((section) => !TOP_BANNER_TYPES.has(section.type));
    return [...topBanners, ...remainingSections];
  }, [sections]);
  const topBannerSections = orderedSections.filter((section) => TOP_BANNER_TYPES.has(section.type));
  const contentSections = orderedSections.filter((section) => !TOP_BANNER_TYPES.has(section.type));

  if (!location) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16">
        <EmptyState
          title="Choose a delivery location"
          subtitle="Set your location to see restaurants, deals and dishes available near you."
          action={
            <button onClick={openPicker} className="btn btn-primary mt-2">
              Select location
            </button>
          }
        />
      </div>
    );
  }

  return (
    <div className="py-0">
      {!loading &&
        topBannerSections.map((section) => (
          <SectionRenderer key={section.token ?? section.type} section={section} isTopBanner />
        ))}

      <div className="mx-auto max-w-6xl space-y-10 px-4 py-8">
        {loading && <RestaurantGridSkeleton />}

        {!loading && error && sections.length === 0 && restaurants.length === 0 && (
          <ErrorState message={error} onRetry={() => setReloadKey((k) => k + 1)} />
        )}

        {!loading &&
          contentSections.map((section) => (
            <SectionRenderer
              key={section.token ?? section.type}
              section={section}
              cuisine={cuisine}
              setCuisine={setCuisine}
              maxPrice={maxPrice}
              setMaxPrice={setMaxPrice}
              openNow={openNow}
              setOpenNow={setOpenNow}
              sort={sort}
              setSort={setSort}
              restaurants={visibleRestaurants}
              restaurantsError={error}
              onRetry={() => setReloadKey((k) => k + 1)}
            />
          ))}
      </div>
    </div>
  );
}

function SectionHeading({ section, fallbackTitle }) {
  // Admin-authored section titles can carry inline HTML for partial
  // highlighting (e.g. `Explore <span style="color:#FF6B35">Categories</span>`)
  // -- trusted admin content, same trust boundary as any other CMS field
  // from this backend, so render it rather than escaping it to literal tags.
  const title = section.title || fallbackTitle;
  return (
    <>
      <h2 className="section-title mb-1" dangerouslySetInnerHTML={{ __html: title }} />
      {section.subtitle && <p className="mb-4 text-sm text-ink-soft">{section.subtitle}</p>}
      {!section.subtitle && <div className="mb-4" />}
    </>
  );
}

function SectionRenderer({
  section,
  cuisine,
  setCuisine,
  maxPrice,
  setMaxPrice,
  openNow,
  setOpenNow,
  sort,
  setSort,
  restaurants,
  restaurantsError,
  onRetry,
  isTopBanner = false,
}) {
  if (!section || section.enabled === false) return null;

  if (CATEGORY_TYPES.has(section.type)) {
    // The built-in "Explore Categories" strip is a cuisine picker only --
    // drop any global-menu-category items (type: 'category'). Admin-authored
    // cuisine_grid sections are left as configured. Fall back to the full
    // list if filtering would empty the strip.
    let items = section.items || [];
    if (section.type === 'categories') {
      const cuisinesOnly = items.filter((it) => it.type === 'cuisine');
      if (cuisinesOnly.length) items = cuisinesOnly;
    }
    if (!items.length) return null;
    return (
      <section>
        <SectionHeading section={section} fallbackTitle="Explore Categories" />
        <CategoryStrip categories={items} selected={cuisine} onSelect={setCuisine} />
      </section>
    );
  }

  if (PROMO_TYPES.has(section.type)) {
    if (!section.items?.length) return null;
    // card_type:'combo_group' is the backend's own signal that this specific
    // item was reshaped into real dish data (image/price/menu_items) by
    // comboGroupPromotionCards() -- render those as product cards. Everything
    // else (including bogo_deals, which shares card_mode:'menu_cards' but
    // stays a plain, imageless Promotion object) goes through the adaptive
    // promo card instead of a broken-looking product card.
    const comboItems = section.items.filter((deal) => deal.card_type === 'combo_group');
    const remaining = section.items.filter((deal) => deal.card_type !== 'combo_group');
    const globalDeals = remaining.filter(isGlobalPromo);
    const scopedDeals = remaining.filter((deal) => !isGlobalPromo(deal));
    return (
      <section>
        <SectionHeading section={section} fallbackTitle="Deals for You" />
        {comboItems.length > 0 && <div className="mb-4"><DishStrip items={comboItems} /></div>}
        {globalDeals.length > 0 && <div className={scopedDeals.length > 0 ? 'mb-4' : ''}><GlobalPromoBanners deals={globalDeals} /></div>}
        <PromoStrip deals={scopedDeals} />
      </section>
    );
  }

  if (section.type === 'popular_dishes') {
    if (!section.items?.length) return null;
    return (
      <section>
        <SectionHeading section={section} fallbackTitle="Popular Dishes" />
        <DishStrip items={section.items} />
      </section>
    );
  }

  if (section.type === 'shop_by_brand') {
    if (!section.items?.length) return null;
    return (
      <section>
        <SectionHeading section={section} fallbackTitle="Shop by Brand" />
        <BrandStrip brands={section.items} />
      </section>
    );
  }

  if (section.type === 'banner_carousel') {
    if (!section.items?.length) return null;
    if (isTopBanner) {
      return (
        <section className="mx-auto mt-6 max-w-6xl px-4">
          <BannerCarousel banners={section.items} variant="hero" />
        </section>
      );
    }
    return (
      <section>
        {(section.title || section.subtitle) && <SectionHeading section={section} fallbackTitle="" />}
        <BannerCarousel banners={section.items} />
      </section>
    );
  }

  if (section.type === 'hero_banner') {
    if (!section.items?.length) return null;
    if (isTopBanner) {
      return (
        <section className="mx-auto mt-6 max-w-6xl px-4">
          <HeroBanner banner={section.items[0]} variant="hero" />
        </section>
      );
    }
    return (
      <section>
        <HeroBanner banner={section.items[0]} />
      </section>
    );
  }

  if (section.type === 'menu_price_filter_config') {
    // Backend hardcodes this as a single nav-action item (label, min/max_price) -
    // surface it as a toggleable chip that filters the restaurant grid below.
    const config = section.items?.[0];
    if (!config) return null;
    const active = maxPrice === config.max_price;
    return (
      <section>
        <button
          onClick={() => setMaxPrice(active ? null : config.max_price)}
          className="chip"
          data-active={active}
        >
          {config.label || section.title || 'Filter'}
          {config.max_price != null && ` · Under ₹${config.max_price}`}
        </button>
      </section>
    );
  }

  if (RESTAURANT_TYPES.has(section.type)) {
    // restaurant_discovery ships items:[] by hardcoded backend contract; other
    // "discovery" style dynamic sections (nearby_restaurants etc.) use the
    // same client_feed:true marker to say the same thing generically.
    const isClientFeed = section.type === 'restaurant_discovery' || section.client_feed === true;

    if (isClientFeed) {
      return (
        <section>
          <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
            <h2 className="section-title">
              {section.title || 'Restaurants near you'}
              {cuisine && <span className="ml-2 align-middle text-sm font-semibold text-ink-soft">· {cuisine}</span>}
            </h2>
            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={() => setOpenNow(!openNow)}
                className="chip"
                data-active={openNow}
                aria-pressed={openNow}
              >
                Open now
              </button>
              <div className="relative">
                <select
                  value={sort}
                  onChange={(e) => setSort(e.target.value)}
                  className="chip cursor-pointer appearance-none pr-8"
                >
                  {SORTS.map((s) => (
                    <option key={s.key} value={s.key}>
                      {s.label}
                    </option>
                  ))}
                </select>
                <ChevronDownIcon
                  size={14}
                  className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-soft"
                />
              </div>
              {cuisine && (
                <button onClick={() => setCuisine(null)} className="text-xs font-bold text-brand">
                  Clear filter
                </button>
              )}
            </div>
          </div>

          {restaurantsError && restaurants.length === 0 && <ErrorState message={restaurantsError} onRetry={onRetry} />}
          {!restaurantsError && restaurants.length === 0 && (
            <EmptyState title="No restaurants found here" subtitle="Try a different location, or clear filters." />
          )}
          {restaurants.length > 0 && (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {restaurants.map((r) => (
                <RestaurantCard key={r.id} restaurant={r} />
              ))}
            </div>
          )}
        </section>
      );
    }

    if (!section.items?.length) return null;
    return (
      <section>
        <SectionHeading section={section} fallbackTitle="Restaurants" />
        <Rail>
          {section.items.map((r) => (
            <div key={r.id} className="flex w-60 shrink-0">
              <RestaurantCard restaurant={r} />
            </div>
          ))}
        </Rail>
      </section>
    );
  }

  // Forward-compatible: an admin can add new section tokens/types at any
  // time. Render a generic strip when the shape supports it, otherwise skip
  // silently rather than guessing at a layout.
  return <GenericSectionStrip section={section} />;
}