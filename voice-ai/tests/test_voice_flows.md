# Voice Flow Test Cases

Run these after the GPU server, Laravel AI tools, and Flutter client are connected.

## Ordering

- "Chicken biryani dikhao."
- "Do add karo."
- "Coke bhi add kar do."
- "Nahi coke hata do."
- "Delivery kitna hai?"
- "Coupon laga do."
- "Mera home address use karo."
- "COD karo."
- "Order place kar do."
- "Wait, address change karna hai."
- "Where is my order?"
- "Cancel my order."

## Failure And Safety

- Invalid voice token is rejected.
- Expired voice token is rejected.
- Another user's address cannot be selected.
- Raw vLLM port is unreachable publicly.
- Laravel timeout returns a friendly retry error.
- No speech returns `no_audio`.
- Barge-in emits `assistant.interrupted`.
- Session expires after TTL.
- Gateway survives malformed tool responses.
- GPU memory does not exceed the configured reserve.
