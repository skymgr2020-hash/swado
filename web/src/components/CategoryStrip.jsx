import Rail from './ui/Rail';

export default function CategoryStrip({ categories, selected, onSelect }) {
  if (!categories?.length) return null;
  return (
    <Rail>
      {categories.map((cat) => {
        const active = selected === cat.name;
        return (
          <button
            key={cat.id}
            onClick={() => onSelect(active ? null : cat.name)}
            className="group flex shrink-0 flex-col items-center gap-2"
            aria-pressed={active}
          >
            <span
              className={`flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-surface-alt ring-2 transition duration-200 group-hover:-translate-y-0.5 ${
                active ? 'ring-brand shadow-brand' : 'ring-line group-hover:ring-line-strong'
              }`}
            >
              {cat.image_url || cat.image ? (
                <img src={cat.image_url || cat.image} alt="" className="h-full w-full object-cover" loading="lazy" />
              ) : (
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" className="text-ink-faint" aria-hidden="true">
                  <path d="M7 2v9M4 2v6a3 3 0 0 0 3 3M10 2v6a3 3 0 0 1-3 3M7 11v11M17 2c-1.5 0-3 2-3 6s1.5 5 3 5 3-1 3-5-1.5-6-3-6ZM17 13v9" />
                </svg>
              )}
            </span>
            <span
              className={`max-w-20 text-center text-xs font-semibold leading-tight ${
                active ? 'text-brand' : 'text-ink-soft'
              }`}
            >
              {cat.name}
            </span>
          </button>
        );
      })}
    </Rail>
  );
}
