import GlobalPromoBanner from './GlobalPromoBanner';

export default function GlobalPromoBanners({ deals }) {
  if (!deals?.length) return null;
  return (
    <div className="flex flex-col gap-3">
      {deals.map((deal) => (
        <GlobalPromoBanner key={deal.id} deal={deal} />
      ))}
    </div>
  );
}
