import BrandCard from './BrandCard';
import Rail from "./ui/Rail";

export default function BrandStrip({ brands }) {
  if (!brands?.length) return null;
  return (
    <Rail>
      {brands.map((brand) => (
        <BrandCard key={brand.restaurant_id ?? brand.id} brand={brand} />
      ))}
    </Rail>
  );
}
