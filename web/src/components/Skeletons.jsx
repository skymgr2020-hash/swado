import { sanitizeMessage } from '../api/client';

export function RestaurantCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
      <div className="sw-skeleton aspect-[4/3] w-full" />
      <div className="space-y-2.5 p-3.5">
        <div className="sw-skeleton h-4 w-3/4 rounded" />
        <div className="sw-skeleton h-3 w-1/2 rounded" />
        <div className="sw-skeleton h-3 w-1/3 rounded" />
      </div>
    </div>
  );
}

export function RestaurantGridSkeleton({ count = 8 }) {
  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      {Array.from({ length: count }).map((_, i) => (
        <RestaurantCardSkeleton key={i} />
      ))}
    </div>
  );
}

export function EmptyState({ title, subtitle, action }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-line-strong bg-surface py-16 text-center">
      <p className="font-display text-lg font-extrabold tracking-tight text-ink">{title}</p>
      {subtitle && <p className="max-w-sm text-sm text-ink-soft">{subtitle}</p>}
      {action}
    </div>
  );
}

export function ErrorState({ message, onRetry }) {
  const safeMessage = sanitizeMessage(message, 0, 'Please try again in a moment.');
  return (
    <div className="flex flex-col items-center justify-center gap-3 rounded-2xl border border-line bg-surface py-16 text-center shadow-card">
      <span className="flex h-11 w-11 items-center justify-center rounded-full bg-[color-mix(in_oklab,var(--color-danger),#fff_82%)] text-danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
          <path d="M12 8v5M12 17h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
        </svg>
      </span>
      <p className="font-display text-lg font-extrabold tracking-tight text-ink">Something went wrong</p>
      <p className="max-w-sm text-sm text-ink-soft">{safeMessage}</p>
      {onRetry && (
        <button onClick={onRetry} className="btn btn-primary mt-1">
          Try again
        </button>
      )}
    </div>
  );
}
