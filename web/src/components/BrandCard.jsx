import { Link } from 'react-router-dom';

export default function BrandCard({ brand }) {
  const logo = brand.logo_image || brand.logo || brand.image;
  return (
    <Link
      to={`/restaurants/${brand.restaurant_id ?? brand.id}`}
      className="group flex w-24 shrink-0 flex-col items-center gap-2 text-center"
    >
      <span className="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-surface-alt ring-2 ring-line transition duration-200 group-hover:-translate-y-0.5 group-hover:ring-line-strong">
        {logo ? (
          <img src={logo} alt={brand.name} className="h-full w-full object-cover" loading="lazy" />
        ) : (
          <span className="text-xl font-extrabold text-ink-faint">{brand.name?.[0]}</span>
        )}
        {brand.is_sponsored && (
          <span className="absolute bottom-0 w-full bg-black/60 py-0.5 text-[9px] font-bold tracking-wide text-white">
            AD
          </span>
        )}
      </span>
      <span className="line-clamp-2 text-xs font-semibold text-ink-soft">{brand.name}</span>
    </Link>
  );
}
