// Fallback renderer for any /api/home/sections entry whose `type` this
// client doesn't have a dedicated component for yet. Section tokens/types
// are entirely admin-configurable (see docs/api-contract/misc.md), so new
// ones can appear at any time — render generically when the item shape
// looks like a simple title/image card, otherwise skip rather than guess.
import Rail from './ui/Rail';

export default function GenericSectionStrip({ section }) {
  const items = section.items;
  if (!Array.isArray(items) || items.length === 0) return null;

  const renderable = items.filter((item) => item && (item.title || item.name));
  if (renderable.length === 0) return null;

  return (
    <section>
      <h2 className="section-title mb-1">{section.title || section.token}</h2>
      {section.subtitle && <p className="mb-4 text-sm text-ink-soft">{section.subtitle}</p>}
      {!section.subtitle && <div className="mb-4" />}
      <Rail>
        {renderable.map((item, idx) => {
          const image = item.image_url || item.image;
          return (
            <div
              key={item.id ?? idx}
              className="flex w-48 shrink-0 flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-card"
            >
              {image && <img src={image} alt={item.title || item.name} className="h-28 w-full object-cover" loading="lazy" />}
              <div className="p-3">
                <p className="line-clamp-1 text-sm font-bold text-ink">{item.title || item.name}</p>
                {item.description && <p className="mt-0.5 line-clamp-2 text-xs text-ink-soft">{item.description}</p>}
              </div>
            </div>
          );
        })}
      </Rail>
    </section>
  );
}
