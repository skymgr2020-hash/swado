import { CheckIcon, ReceiptIcon, StoreIcon, BikeIcon } from '../ui/icons';

// Status vocabulary from the Flutter order model / tracking screen.
const STEPS = [
  { key: 'placed', label: 'Order placed', match: ['pending', 'placed', 'created'], Icon: ReceiptIcon },
  { key: 'confirmed', label: 'Confirmed', match: ['confirmed', 'accepted'], Icon: CheckIcon },
  { key: 'preparing', label: 'Preparing', match: ['preparing', 'cooking', 'in_kitchen'], Icon: StoreIcon },
  { key: 'ready', label: 'Ready / picked up', match: ['ready', 'ready_for_pickup', 'picked_up'], Icon: CheckIcon },
  { key: 'on_the_way', label: 'Out for delivery', match: ['on_the_way', 'out_for_delivery', 'dispatched'], Icon: BikeIcon },
  { key: 'delivered', label: 'Delivered', match: ['delivered', 'completed'], Icon: CheckIcon },
];

function activeIndex(status) {
  const s = String(status || '').toLowerCase();
  for (let i = STEPS.length - 1; i >= 0; i -= 1) {
    if (STEPS[i].match.includes(s)) return i;
  }
  return 0;
}

export default function OrderStatusTimeline({ status }) {
  const cancelled = ['cancelled', 'canceled', 'rejected', 'refunded'].includes(
    String(status || '').toLowerCase()
  );

  if (cancelled) {
    return (
      <div className="rounded-2xl border border-line bg-[color-mix(in_oklab,var(--color-danger),#fff_86%)] p-4 text-sm font-semibold text-danger">
        This order was {String(status).toLowerCase()}.
      </div>
    );
  }

  const current = activeIndex(status);

  return (
    <ol className="relative">
      {STEPS.map((step, i) => {
        const done = i < current;
        const active = i === current;
        const last = i === STEPS.length - 1;
        return (
          <li key={step.key} className="flex gap-3.5 pb-6 last:pb-0">
            <div className="relative flex flex-col items-center">
              <span
                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ring-4 ring-surface ${
                  done
                    ? 'bg-veg text-white'
                    : active
                      ? 'bg-brand text-white'
                      : 'bg-surface-alt text-ink-faint'
                }`}
              >
                {done ? <CheckIcon size={16} /> : <step.Icon size={16} />}
              </span>
              {!last && (
                <span
                  className={`mt-1 w-0.5 flex-1 ${done ? 'bg-veg' : 'bg-line-strong'}`}
                  style={{ minHeight: 24 }}
                />
              )}
            </div>
            <div className="pt-1.5">
              <p
                className={`text-sm font-bold ${
                  done || active ? 'text-ink' : 'text-ink-faint'
                }`}
              >
                {step.label}
              </p>
              {active && (
                <p className="mt-0.5 text-xs font-semibold text-brand">In progress</p>
              )}
            </div>
          </li>
        );
      })}
    </ol>
  );
}
