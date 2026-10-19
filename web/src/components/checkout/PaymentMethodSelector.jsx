import { CashIcon, WalletIcon, CardIcon, CheckIcon } from '../ui/icons';

// Mirrors the Flutter checkout payment sheet: Online gateway / Wallet / COD.
// COD and Wallet resolve entirely server-side (payment_method in POST /orders).
// Online runs Razorpay web checkout (/payments/checkout/create + /verify) when
// the store's gateway is Razorpay; otherwise it's disabled with an app hint.
const METHODS = [
  { key: 'online', label: 'Pay online', Icon: CardIcon },
  { key: 'wallet', label: 'Swado Wallet', Icon: WalletIcon },
  {
    key: 'cod',
    label: 'Cash on delivery',
    hint: 'Pay the delivery partner when your order arrives',
    Icon: CashIcon,
  },
];

export default function PaymentMethodSelector({ value, onChange, walletBalance, total, onlineAvailable = false }) {
  const walletCovers = walletBalance != null && total != null && walletBalance >= total && total > 0;

  return (
    <div className="space-y-2">
      {METHODS.map((m) => {
        const isWallet = m.key === 'wallet';
        const isOnline = m.key === 'online';
        const disabled = (isWallet && !walletCovers) || (isOnline && !onlineAvailable);
        const selected = value === m.key;

        let hint = m.hint;
        if (isWallet) {
          hint =
            walletBalance == null
              ? 'Checking balance…'
              : walletCovers
                ? `Balance ₹${Number(walletBalance).toFixed(0)} — covers this order`
                : `Balance ₹${Number(walletBalance).toFixed(0)} is below the bill total`;
        } else if (isOnline) {
          hint = onlineAvailable
            ? 'UPI, cards & netbanking via Razorpay'
            : 'Card & UPI payments are available in the Swado app';
        }

        return (
          <button
            key={m.key}
            type="button"
            disabled={disabled}
            aria-pressed={selected}
            onClick={() => !disabled && onChange(m.key)}
            className={`flex w-full items-center gap-3 rounded-2xl border p-3.5 text-left transition ${
              selected ? 'border-brand bg-brand-tint' : 'border-line-strong bg-surface hover:border-brand'
            } ${disabled ? 'cursor-not-allowed opacity-55 hover:border-line-strong' : ''}`}
          >
            <span
              className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${
                selected ? 'bg-brand text-white' : 'bg-surface-alt text-ink-soft'
              }`}
            >
              <m.Icon size={19} />
            </span>
            <span className="min-w-0 flex-1">
              <span className="block text-sm font-bold text-ink">{m.label}</span>
              {hint && <span className="mt-0.5 block text-xs text-ink-soft">{hint}</span>}
            </span>
            <span
              className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                selected ? 'border-brand bg-brand text-white' : 'border-line-strong'
              }`}
            >
              {selected && <CheckIcon size={12} />}
            </span>
          </button>
        );
      })}
    </div>
  );
}
