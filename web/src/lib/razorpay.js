// Razorpay web checkout (checkout.js drop-in). Mirrors the native flow in
// customer/lib/services/flexible_order_payment_service.dart: the backend's
// /payments/checkout/create hands back the key + gateway order id + amount,
// we open the modal, and resolve with the fields /payments/checkout/verify
// expects. Resolves `null` when the customer dismisses the modal.
const SRC = 'https://checkout.razorpay.com/v1/checkout.js';
let loader = null;

export function loadRazorpay() {
  if (typeof window !== 'undefined' && window.Razorpay) return Promise.resolve(true);
  if (loader) return loader;
  loader = new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = SRC;
    s.async = true;
    s.onload = () => resolve(true);
    s.onerror = () => {
      loader = null;
      reject(new Error('Could not load the payment gateway.'));
    };
    document.body.appendChild(s);
  });
  return loader;
}

export async function openRazorpayCheckout({ key, amount, currency = 'INR', orderId, name, description, prefill }) {
  await loadRazorpay();
  return new Promise((resolve, reject) => {
    let settled = false;
    const rzp = new window.Razorpay({
      key,
      amount,
      currency,
      order_id: orderId,
      name: name || 'Swado',
      description: description || 'Order payment',
      prefill: prefill || {},
      theme: { color: '#ff5a1f' },
      handler: (response) => {
        settled = true;
        resolve({
          razorpay_payment_id: response.razorpay_payment_id,
          razorpay_order_id: response.razorpay_order_id,
          razorpay_signature: response.razorpay_signature,
        });
      },
      modal: {
        ondismiss: () => {
          if (!settled) resolve(null);
        },
      },
    });
    rzp.on('payment.failed', (resp) => {
      settled = true;
      reject(new Error(resp?.error?.description || 'Payment failed. You were not charged.'));
    });
    rzp.open();
  });
}
