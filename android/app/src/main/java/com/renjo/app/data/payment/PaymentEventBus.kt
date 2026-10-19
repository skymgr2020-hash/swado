package com.renjo.app.data.payment

import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Razorpay and Cashfree's Android SDKs both report results by invoking
 * listener interfaces implemented on [com.renjo.app.MainActivity] (not via
 * a normal Compose callback), so the results are funneled here and
 * collected by whichever suspend function is currently awaiting a gateway
 * result. Stripe's PaymentSheet is Compose-native (`rememberPaymentSheet`)
 * and doesn't need this bridge.
 */
sealed interface GatewayCallbackEvent {
    data class RazorpaySuccess(val paymentId: String, val orderId: String?, val signature: String?) : GatewayCallbackEvent
    data class RazorpayError(val code: Int, val message: String?) : GatewayCallbackEvent
    data class CashfreeSuccess(val orderId: String) : GatewayCallbackEvent
    data class CashfreeError(val orderId: String, val message: String) : GatewayCallbackEvent
}

@Singleton
class PaymentEventBus @Inject constructor() {
    private val _events = MutableSharedFlow<GatewayCallbackEvent>(extraBufferCapacity = 4)
    val events: SharedFlow<GatewayCallbackEvent> = _events.asSharedFlow()

    fun emit(event: GatewayCallbackEvent) {
        _events.tryEmit(event)
    }
}
