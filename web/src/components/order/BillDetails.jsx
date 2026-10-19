// Renders the bill breakdown from a /orders/summary response (or an order
// object, which carries the same field names). Rows with no value are hidden.
import { useState } from 'react';

function money(v) {
  return `₹${Math.abs(Number(v ?? 0)).toFixed(0)}`;
}

function Row({ label, value, strong = false, credit = false }) {
  if (value == null || Number.isNaN(Number(value))) return null;
  return (
    <div className={`flex justify-between ${strong ? 'text-base font-extrabold text-ink' : credit ? 'font-semibold text-veg' : 'text-ink-soft'}`}>
      <span>{label}</span>
      <span>
        {credit || Number(value) < 0 ? '−' : ''}
        {money(value)}
      </span>
    </div>
  );
}

// CGST/SGST split from summary.gst_breakdown.rate_summary (present only when the
// platform runs GST invoicing). Additive — collapses to nothing otherwise.
function GstBreakdown({ gst }) {
  const [open, setOpen] = useState(false);
  const rows = Array.isArray(gst?.rate_summary) ? gst.rate_summary : [];
  if (!rows.length) return null;

  return (
    <div className="text-ink-soft">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between text-left"
      >
        <span className="underline decoration-dotted underline-offset-2">GST breakdown</span>
        <span className="text-xs">{open ? 'Hide' : 'View'}</span>
      </button>
      {open && (
        <div className="mt-1 space-y-1 rounded-lg bg-surface-muted/60 p-2 text-xs">
          {rows.map((r, i) => (
            <div key={i} className="flex justify-between">
              <span>
                {r.label || `GST @ ${r.rate}%`}
                {r.rate != null && !r.label ? '' : ` (${r.rate}%)`}
              </span>
              <span>
                CGST {money((Number(r.cgst ?? 0)))} · SGST {money((Number(r.sgst ?? 0)))}
              </span>
            </div>
          ))}
          {gst.sec_9_5_note && (
            <p className="pt-1 text-[11px] leading-snug text-ink-soft">{gst.sec_9_5_note}</p>
          )}
        </div>
      )}
    </div>
  );
}

export default function BillDetails({ summary, taxLabel = 'Taxes & charges' }) {
  if (!summary) return null;
  const deliveryFee = summary.payable_delivery_fee ?? summary.delivery_fee;
  const discount = Number(summary.discount ?? 0);
  const total = summary.total ?? summary.grand_total ?? summary.subtotal;
  const gst = summary.gst_breakdown
    ? { ...summary.gst_breakdown, sec_9_5_note: summary.sec_9_5_note }
    : null;

  return (
    <div className="space-y-1.5 text-sm">
      <Row label="Item total" value={summary.subtotal} />
      <Row label="Delivery fee" value={deliveryFee} />
      <Row label="Platform fee" value={summary.platform_fee} />
      <Row label={summary.tax_label || taxLabel} value={summary.tax} />
      {gst && <GstBreakdown gst={gst} />}
      {discount > 0 && <Row label="Discount" value={discount} credit />}
      {Number(summary.wallet_applied ?? 0) > 0 && <Row label="Wallet" value={summary.wallet_applied} credit />}
      <div className="mt-2 flex justify-between border-t border-line pt-2 text-base font-extrabold text-ink">
        <span>To pay</span>
        <span>{money(total)}</span>
      </div>
    </div>
  );
}
