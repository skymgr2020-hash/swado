import { Link } from 'react-router-dom';
import Rail from "./ui/Rail";

// Banner `link` values can be an internal path, a bare restaurant id, or a
// foodflow://restaurants/{id}-style deeplink (see customer_restaurant_deeplink_template
// in /api/app/branding) -- resolve whichever shape shows up into a route
// this app actually has, and fall back to a plain external link otherwise.
function resolveBannerHref(banner) {
  if (banner.restaurant_id) return { to: `/restaurants/${banner.restaurant_id}` };
  const link = banner.link;
  if (!link) return null;
  const restaurantMatch = String(link).match(/restaurants?\/(\d+)/i);
  if (restaurantMatch) return { to: `/restaurants/${restaurantMatch[1]}` };
  if (/^https?:\/\//i.test(link)) return { href: link };
  if (link.startsWith('/')) return { to: link };
  return null;
}

function BannerImage({ banner, loading = 'lazy' }) {
  const target = resolveBannerHref(banner);
  const image = banner.image_url || banner.image;
  const content = image ? (
    <img src={image} alt={banner.title || ''} className="h-full w-full object-cover object-center" loading={loading} />
  ) : (
    <div className="flex h-full w-full items-center justify-center bg-neutral-100 text-neutral-300">No image</div>
  );

  if (target?.to) {
    return (
      <Link to={target.to} className="block h-full w-full">
        {content}
      </Link>
    );
  }
  if (target?.href) {
    return (
      <a href={target.href} target="_blank" rel="noopener noreferrer" className="block h-full w-full">
        {content}
      </a>
    );
  }
  return content;
}

export default function BannerCarousel({ banners, variant = 'default' }) {
  if (!banners?.length) return null;

  if (variant === 'hero') {
    return (
      <div className="w-full overflow-hidden rounded-2xl border border-line bg-surface-alt shadow-card">
        <div className="no-scrollbar flex snap-x snap-mandatory overflow-x-auto">
          {banners.map((banner, index) => (
            <div key={banner.id} className="aspect-[16/7] w-full flex-none snap-start overflow-hidden sm:aspect-[3/1]">
              <BannerImage banner={banner} loading={index === 0 ? 'eager' : 'lazy'} />
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <Rail>
      {banners.map((banner) => (
        <div
          key={banner.id}
          className="aspect-[16/6] w-[min(88vw,28rem)] shrink-0 overflow-hidden rounded-2xl border border-line shadow-card"
        >
          <BannerImage banner={banner} />
        </div>
      ))}
    </Rail>
  );
}

export { resolveBannerHref, BannerImage };