import { Link } from 'react-router-dom';
import { StarIcon, ClockIcon } from './ui/icons';

export default function RestaurantCard({ restaurant }) {
  const {
    id,
    banner_image,
    cuisine,
    rating,
    total_ratings,
    eta_range,
    amount_for_one,
    is_pure_veg,
    is_featured,
    next_opening_label,
  } = restaurant;
  const name = restaurant.name || restaurant.title || 'Restaurant';
  // Discovery feeds send `is_open_now`; the search index shape omits it -- only
  // treat a restaurant as closed when the flag is explicitly false.
  const is_open_now = restaurant.is_open_now ?? restaurant.is_open ?? true;

  return (
    <Link
      to={`/restaurants/${id}`}
      className="group flex h-full w-full flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-card transition duration-200 hover:-translate-y-0.5 hover:shadow-card-hover"
    >
      <div className="relative aspect-[4/3] w-full shrink-0 overflow-hidden bg-surface-alt">
        {banner_image ? (
          <img
            src={banner_image}
            alt={name}
            className="h-full w-full object-cover transition duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-105"
            loading="lazy"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-sm text-ink-faint">No image</div>
        )}

        <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/25 to-transparent" />

        {is_featured && (
          <span className="absolute left-2.5 top-2.5 rounded-full bg-gold-tint px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-gold">
            Featured
          </span>
        )}
        {is_pure_veg && (
          <span className="absolute right-2.5 top-2.5 rounded-full bg-surface/90 px-2 py-1 text-[11px] font-bold text-veg backdrop-blur-sm">
            Pure Veg
          </span>
        )}

        {!is_open_now && (
          <div className="absolute inset-0 flex items-center justify-center bg-black/55">
            <span className="rounded-full bg-surface/95 px-3.5 py-1.5 text-xs font-bold text-ink">
              {next_opening_label || 'Closed'}
            </span>
          </div>
        )}
      </div>

      <div className="flex flex-1 flex-col p-3.5">
        <div className="flex items-start justify-between gap-2">
          <h3 className="line-clamp-1 text-[15px] font-extrabold tracking-tight text-ink">{name}</h3>
          {rating != null && (
            <span className="flex shrink-0 items-center gap-1 rounded-lg bg-rating px-1.5 py-0.5 text-xs font-extrabold text-white">
              {Number(rating).toFixed(1)}
              <StarIcon size={11} className="text-white" />
            </span>
          )}
        </div>

        <p className="mt-1 line-clamp-1 text-[13px] text-ink-soft">
          {Array.isArray(cuisine) ? cuisine.join(', ') : cuisine || ' '}
        </p>

        {/* Meta pinned to the bottom so cards of different content stay equal height */}
        <div className="mt-auto pt-2.5">
          <div className="flex min-h-[18px] items-center gap-2 text-[13px] font-medium text-ink-soft">
            {eta_range && (
              <span className="flex items-center gap-1">
                <ClockIcon size={13} className="text-ink-faint" />
                {eta_range}
              </span>
            )}
            {eta_range && amount_for_one != null && <span className="text-ink-faint">•</span>}
            {amount_for_one != null && <span>₹{amount_for_one} for one</span>}
          </div>
          <p className="mt-1 min-h-[14px] text-[11px] text-ink-faint">
            {total_ratings > 0 ? `${total_ratings} ratings` : ' '}
          </p>
        </div>
      </div>
    </Link>
  );
}
