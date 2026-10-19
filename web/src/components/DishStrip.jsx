import DishCard from './DishCard';
import Rail from "./ui/Rail";

export default function DishStrip({ items }) {
  if (!items?.length) return null;
  return (
    <Rail>
      {items.map((item) => (
        <DishCard key={item.menu_item_id ?? item.id} item={item} />
      ))}
    </Rail>
  );
}
