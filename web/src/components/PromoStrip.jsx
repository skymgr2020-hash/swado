import PromoCard from './PromoCard';
import Rail from "./ui/Rail";

export default function PromoStrip({ deals }) {
  if (!deals?.length) return null;
  return (
    <Rail>
      {deals.map((deal) => (
        <PromoCard key={deal.id} deal={deal} />
      ))}
    </Rail>
  );
}
