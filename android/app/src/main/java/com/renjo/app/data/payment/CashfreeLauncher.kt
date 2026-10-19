package com.renjo.app.data.payment

import android.app.Activity
import com.cashfree.pg.api.CFPaymentGatewayService
import com.cashfree.pg.core.api.CFSession
import com.cashfree.pg.core.api.webcheckout.CFWebCheckoutPayment
import com.renjo.app.data.network.dto.PaymentSessionDto
import kotlinx.coroutines.CompletableDeferred
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.CoroutineStart
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import javax.inject.Inject

class CashfreeLauncher @Inject constructor(
    private val eventBus: PaymentEventBus,
) {
    /** Web-checkout drop-in (not the per-method UPI/card builders) so one
     * integration covers every payment mode Cashfree supports, matching how
     * the backend already computes the full price server-side regardless of
     * method. Result arrives via [PaymentEventBus] from
     * [com.renjo.app.MainActivity]'s registered CFCheckoutResponseCallback. */
    suspend fun launch(activity: Activity, session: PaymentSessionDto): GatewayCallbackEvent {
        val orderId = requireNotNull(session.orderId) { "Cashfree session missing order_id" }
        val sessionId = requireNotNull(session.paymentSessionId) { "Cashfree session missing payment_session_id" }

        val resultDeferred = CompletableDeferred<GatewayCallbackEvent>()
        val collector = CoroutineScope(Dispatchers.Main.immediate).launch(start = CoroutineStart.UNDISPATCHED) {
            val event = eventBus.events.first {
                it is GatewayCallbackEvent.CashfreeSuccess || it is GatewayCallbackEvent.CashfreeError
            }
            resultDeferred.complete(event)
        }

        val environment = if (session.environment == "production") {
            CFSession.Environment.PRODUCTION
        } else {
            CFSession.Environment.SANDBOX
        }
        val cfSession = CFSession.CFSessionBuilder()
            .setEnvironment(environment)
            .setPaymentSessionID(sessionId)
            .setOrderId(orderId)
            .build()
        val checkoutPayment = CFWebCheckoutPayment.CFWebCheckoutPaymentBuilder()
            .setSession(cfSession)
            .build()

        CFPaymentGatewayService.getInstance().doPayment(activity, checkoutPayment)
        val result = resultDeferred.await()
        collector.cancel()
        return result
    }
}
