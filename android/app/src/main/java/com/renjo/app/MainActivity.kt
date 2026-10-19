package com.renjo.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.cashfree.pg.api.CFPaymentGatewayService
import com.cashfree.pg.core.api.callback.CFCheckoutResponseCallback
import com.cashfree.pg.core.api.exception.CFException
import com.cashfree.pg.core.api.utils.CFErrorResponse
import com.razorpay.PaymentData
import com.razorpay.PaymentResultWithDataListener
import com.renjo.app.data.payment.GatewayCallbackEvent
import com.renjo.app.data.payment.PaymentEventBus
import com.renjo.app.ui.navigation.SwadoApp
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : ComponentActivity(), PaymentResultWithDataListener, CFCheckoutResponseCallback {

    @Inject
    lateinit var paymentEventBus: PaymentEventBus

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        try {
            CFPaymentGatewayService.getInstance().setCheckoutCallback(this)
        } catch (e: CFException) {
            e.printStackTrace()
        }
        setContent { SwadoApp() }
    }

    // --- Razorpay (Checkout.open opened from RazorpayLauncher) ---
    override fun onPaymentSuccess(razorpayPaymentId: String?, paymentData: PaymentData?) {
        if (razorpayPaymentId == null) {
            paymentEventBus.emit(GatewayCallbackEvent.RazorpayError(0, "Missing payment id"))
            return
        }
        paymentEventBus.emit(
            GatewayCallbackEvent.RazorpaySuccess(
                paymentId = razorpayPaymentId,
                orderId = paymentData?.orderId,
                signature = paymentData?.signature,
            ),
        )
    }

    override fun onPaymentError(code: Int, response: String?, paymentData: PaymentData?) {
        paymentEventBus.emit(GatewayCallbackEvent.RazorpayError(code, response))
    }

    // --- Cashfree (CFPaymentGatewayService.doPayment opened from CashfreeLauncher) ---
    override fun onPaymentVerify(orderID: String) {
        paymentEventBus.emit(GatewayCallbackEvent.CashfreeSuccess(orderID))
    }

    override fun onPaymentFailure(cfErrorResponse: CFErrorResponse, orderID: String) {
        paymentEventBus.emit(GatewayCallbackEvent.CashfreeError(orderID, cfErrorResponse.message))
    }
}
