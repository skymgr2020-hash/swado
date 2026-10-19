package com.renjo.app.ui.screens.checkout

import android.app.Activity
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.cart.CartRepository
import com.renjo.app.data.cart.RestaurantCart
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.AddressDto
import com.renjo.app.data.network.dto.OrderItemRequest
import com.renjo.app.data.network.dto.OrderSummaryDto
import com.renjo.app.data.network.dto.OrderSummaryRequest
import com.renjo.app.data.network.dto.PaymentSessionDto
import com.renjo.app.data.network.dto.PlaceOrderRequest
import com.renjo.app.data.network.dto.VerifyPaymentRequest
import com.renjo.app.data.payment.CashfreeLauncher
import com.renjo.app.data.payment.GatewayCallbackEvent
import com.renjo.app.data.payment.RazorpayLauncher
import com.renjo.app.data.repository.AddressRepository
import com.renjo.app.data.repository.OrderRepository
import com.renjo.app.data.repository.PaymentRepository
import com.renjo.app.data.session.SessionManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface CheckoutUiState {
    data object Loading : CheckoutUiState
    data class NeedsAddress(val message: String? = null) : CheckoutUiState
    data class Ready(val addresses: List<AddressDto>, val selectedAddress: AddressDto, val summary: OrderSummaryDto?, val summaryLoading: Boolean) : CheckoutUiState
    data class Error(val message: String) : CheckoutUiState
}

sealed interface PlaceOrderState {
    data object Idle : PlaceOrderState
    data object Placing : PlaceOrderState
    /** Order row exists server-side but is unpaid; client must run the
     * gateway SDK next (Flow A: POST /orders/{id}/pay already happened to
     * produce [session] — see PaymentRepository). */
    data class RequiresPayment(val orderId: Int, val session: PaymentSessionDto) : PlaceOrderState
    data object VerifyingPayment : PlaceOrderState
    data class Success(val orderId: Int) : PlaceOrderState
    data class Error(val message: String) : PlaceOrderState
}

/** All methods the merchant has enabled are selectable — the backend is the
 * source of truth for what's actually configured (falls back to showing all
 * three gateways when the account has none configured yet, so the picker is
 * exercisable during setup). */
data class PaymentOption(
    val key: String,
    val title: String,
    val subtitle: String,
    val enabled: Boolean,
)

private val gatewayDisplayNames = mapOf(
    "razorpay" to "Razorpay",
    "stripe" to "Stripe",
    "cashfree" to "Cashfree",
)

@HiltViewModel
class CheckoutViewModel @Inject constructor(
    private val addressRepository: AddressRepository,
    private val orderRepository: OrderRepository,
    private val paymentRepository: PaymentRepository,
    private val cartRepository: CartRepository,
    private val sessionManager: SessionManager,
    private val razorpayLauncher: RazorpayLauncher,
    private val cashfreeLauncher: CashfreeLauncher,
) : ViewModel() {

    private val _uiState = MutableStateFlow<CheckoutUiState>(CheckoutUiState.Loading)
    val uiState: StateFlow<CheckoutUiState> = _uiState.asStateFlow()

    private val _placeOrderState = MutableStateFlow<PlaceOrderState>(PlaceOrderState.Idle)
    val placeOrderState: StateFlow<PlaceOrderState> = _placeOrderState.asStateFlow()

    private val _selectedPaymentMethod = MutableStateFlow("cod")
    val selectedPaymentMethod: StateFlow<String> = _selectedPaymentMethod.asStateFlow()

    val cart: RestaurantCart? get() = cartRepository.cart.value

    val userPhone: String? get() = sessionManager.currentUser.value?.phone

    val paymentOptions: List<PaymentOption>
        get() {
            val settings = sessionManager.currentUser.value?.settings
            val options = mutableListOf<PaymentOption>()
            if (settings?.codEnabled != false) {
                options += PaymentOption("cod", "Cash / UPI on Delivery", "Pay when your order arrives", enabled = true)
            }
            val enabledGateways = settings?.enabledPaymentGateways.orEmpty()
                .ifEmpty { gatewayDisplayNames.keys.toList() }
            enabledGateways.forEach { key ->
                val name = gatewayDisplayNames[key] ?: key.replaceFirstChar { it.uppercase() }
                options += PaymentOption(key, "Pay Online via $name", "Cards, UPI, wallets & netbanking", enabled = true)
            }
            return options
        }

    fun selectPaymentMethod(key: String) {
        _selectedPaymentMethod.value = key
    }

    fun paymentMethodTitle(): String =
        paymentOptions.firstOrNull { it.key == _selectedPaymentMethod.value }?.title ?: "Cash / UPI on Delivery"

    init {
        loadAddresses()
    }

    fun loadAddresses() {
        val currentCart = cart
        if (currentCart == null || currentCart.items.isEmpty()) {
            _uiState.value = CheckoutUiState.Error("Your cart is empty.")
            return
        }
        _uiState.value = CheckoutUiState.Loading
        viewModelScope.launch {
            when (val result = addressRepository.list(currentCart.restaurantId)) {
                is NetworkResult.Success -> {
                    val addresses = result.data
                    if (addresses.isEmpty()) {
                        _uiState.value = CheckoutUiState.NeedsAddress()
                    } else {
                        val selected = addresses.firstOrNull { it.isDefault } ?: addresses.first()
                        _uiState.value = CheckoutUiState.Ready(addresses, selected, summary = null, summaryLoading = true)
                        refreshSummary(selected)
                    }
                }
                is NetworkResult.Error -> _uiState.value = CheckoutUiState.Error(result.message)
            }
        }
    }

    fun selectAddress(address: AddressDto) {
        val current = _uiState.value as? CheckoutUiState.Ready ?: return
        _uiState.value = current.copy(selectedAddress = address, summaryLoading = true)
        refreshSummary(address)
    }

    private fun refreshSummary(address: AddressDto) {
        val currentCart = cart ?: return
        viewModelScope.launch {
            val request = OrderSummaryRequest(
                restaurantId = currentCart.restaurantId,
                items = currentCart.items.map { OrderItemRequest(it.menuItemId, it.quantity, it.selectedAddOns.ifEmpty { null }) },
                deliveryAddressId = address.id,
            )
            val result = orderRepository.summary(request)
            val ready = _uiState.value as? CheckoutUiState.Ready ?: return@launch
            _uiState.value = when (result) {
                is NetworkResult.Success -> ready.copy(summary = result.data, summaryLoading = false)
                is NetworkResult.Error -> ready.copy(summary = null, summaryLoading = false)
            }
        }
    }

    fun placeOrder(specialInstructions: String?) {
        val currentCart = cart ?: return
        val ready = _uiState.value as? CheckoutUiState.Ready ?: return
        _placeOrderState.value = PlaceOrderState.Placing
        viewModelScope.launch {
            val request = PlaceOrderRequest(
                restaurantId = currentCart.restaurantId,
                items = currentCart.items.map { OrderItemRequest(it.menuItemId, it.quantity, it.selectedAddOns.ifEmpty { null }) },
                deliveryAddressId = ready.selectedAddress.id,
                paymentMethod = _selectedPaymentMethod.value,
                specialInstructions = specialInstructions?.ifBlank { null },
            )
            when (val result = orderRepository.placeOrder(request)) {
                is NetworkResult.Success -> {
                    val data = result.data
                    val orderId = data.order?.id ?: 0
                    if (data.requiresPayment && orderId != 0) {
                        startGatewayPayment(orderId)
                    } else {
                        cartRepository.clear()
                        _placeOrderState.value = PlaceOrderState.Success(orderId)
                    }
                }
                is NetworkResult.Error -> _placeOrderState.value = PlaceOrderState.Error(result.message)
            }
        }
    }

    private suspend fun startGatewayPayment(orderId: Int) {
        when (val result = paymentRepository.payForOrder(orderId, _selectedPaymentMethod.value)) {
            is NetworkResult.Success -> _placeOrderState.value = PlaceOrderState.RequiresPayment(orderId, result.data)
            is NetworkResult.Error -> _placeOrderState.value = PlaceOrderState.Error(result.message)
        }
    }

    /** Called from the Composable (which owns the Activity reference gateway
     * SDKs require) once a [PlaceOrderState.RequiresPayment] is observed for
     * a razorpay/cashfree session — Stripe's PaymentSheet is Compose-native
     * and calls [verifyStripePayment] directly instead of going through here. */
    fun launchGateway(activity: Activity, orderId: Int, session: PaymentSessionDto) {
        viewModelScope.launch {
            val event = when (session.gateway) {
                "cashfree" -> cashfreeLauncher.launch(activity, session)
                else -> razorpayLauncher.launch(activity, session, userPhone)
            }
            completeGatewayPayment(orderId, session.gateway ?: _selectedPaymentMethod.value, event)
        }
    }

    private suspend fun completeGatewayPayment(orderId: Int, gateway: String, event: GatewayCallbackEvent) {
        _placeOrderState.value = PlaceOrderState.VerifyingPayment
        val request = when (event) {
            is GatewayCallbackEvent.RazorpaySuccess -> VerifyPaymentRequest(
                orderId = orderId,
                paymentId = event.paymentId,
                paymentMethod = gateway,
                gateway = gateway,
                paymentGateway = gateway,
                paymentGatewayProvider = gateway,
                razorpayOrderId = event.orderId,
                razorpaySignature = event.signature,
            )
            is GatewayCallbackEvent.CashfreeSuccess -> VerifyPaymentRequest(
                orderId = orderId,
                paymentId = event.orderId,
                paymentMethod = gateway,
                gateway = gateway,
                paymentGateway = gateway,
                paymentGatewayProvider = gateway,
            )
            is GatewayCallbackEvent.RazorpayError, is GatewayCallbackEvent.CashfreeError -> {
                paymentRepository.cancelPayment(orderId, "Payment was not completed on device")
                _placeOrderState.value = PlaceOrderState.Error("Payment was not completed. Please try again.")
                return
            }
        }
        finishVerification(orderId, request)
    }

    /** Stripe's PaymentSheet result is read directly in Compose (see
     * CheckoutScreen) since `rememberPaymentSheet` must be called during
     * composition — this is the shared tail-end both paths call into. */
    fun verifyStripePayment(orderId: Int, paymentIntentId: String) {
        viewModelScope.launch {
            _placeOrderState.value = PlaceOrderState.VerifyingPayment
            finishVerification(
                orderId,
                VerifyPaymentRequest(
                    orderId = orderId,
                    paymentId = paymentIntentId,
                    paymentMethod = "stripe",
                    gateway = "stripe",
                    paymentGateway = "stripe",
                    paymentGatewayProvider = "stripe",
                    stripePaymentIntentId = paymentIntentId,
                ),
            )
        }
    }

    fun onStripeCancelledOrFailed(orderId: Int) {
        viewModelScope.launch {
            paymentRepository.cancelPayment(orderId, "Payment was not completed on device")
            _placeOrderState.value = PlaceOrderState.Error("Payment was not completed. Please try again.")
        }
    }

    private suspend fun finishVerification(orderId: Int, request: VerifyPaymentRequest) {
        when (val result = paymentRepository.verifyPayment(request)) {
            is NetworkResult.Success -> {
                cartRepository.clear()
                _placeOrderState.value = PlaceOrderState.Success(orderId)
            }
            is NetworkResult.Error -> _placeOrderState.value = PlaceOrderState.Error(result.message)
        }
    }
}
