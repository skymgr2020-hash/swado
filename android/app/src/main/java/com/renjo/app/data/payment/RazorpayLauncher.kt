package com.renjo.app.data.payment

import android.app.Activity
import com.razorpay.Checkout
import com.renjo.app.data.network.dto.PaymentSessionDto
import kotlinx.coroutines.CompletableDeferred
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.CoroutineStart
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import org.json.JSONObject
import javax.inject.Inject

class RazorpayLauncher @Inject constructor(
    private val eventBus: PaymentEventBus,
) {
    /** Opens Razorpay Checkout and suspends until [com.renjo.app.MainActivity]'s
     * listener callbacks report a result via [PaymentEventBus]. The collector
     * is started with UNDISPATCHED so it's subscribed before `Checkout.open`
     * fires — otherwise a fast user-driven callback could race the subscribe. */
    suspend fun launch(activity: Activity, session: PaymentSessionDto, contactPhone: String?): GatewayCallbackEvent {
        val resultDeferred = CompletableDeferred<GatewayCallbackEvent>()
        val collector = CoroutineScope(Dispatchers.Main.immediate).launch(start = CoroutineStart.UNDISPATCHED) {
            val event = eventBus.events.first {
                it is GatewayCallbackEvent.RazorpaySuccess || it is GatewayCallbackEvent.RazorpayError
            }
            resultDeferred.complete(event)
        }

        val amountPaise = session.amountMinor ?: (((session.amount ?: 0.0) * 100).toLong())
        val options = JSONObject().apply {
            put("order_id", session.orderId)
            put("amount", amountPaise)
            put("currency", session.currency ?: "INR")
            put("name", "Swado")
            put("description", "Order payment")
            if (!contactPhone.isNullOrBlank()) {
                put("prefill", JSONObject().apply { put("contact", contactPhone) })
            }
        }

        Checkout().apply { setKeyID(session.key) }.open(activity, options)
        val result = resultDeferred.await()
        collector.cancel()
        return result
    }
}
