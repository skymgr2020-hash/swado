import { BannerImage } from './BannerCarousel';

export default function HeroBanner({ banner, variant = 'default' }) {
  if (!banner) return null;

  if (variant === 'hero') {
    return (
      <div className="aspect-[16/7] w-full overflow-hidden rounded-2xl border border-line bg-surface-alt shadow-card sm:aspect-[3/1]">
        <BannerImage banner={banner} loading="eager" />
      </div>
    );
  }

  return (
    <div className="aspect-[16/6] w-full overflow-hidden rounded-2xl border border-line shadow-card sm:aspect-[3/1]">
      <BannerImage banner={banner} />
    </div>
  );
}