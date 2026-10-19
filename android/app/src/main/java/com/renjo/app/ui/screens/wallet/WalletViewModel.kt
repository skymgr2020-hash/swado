package com.renjo.app.ui.screens.wallet

import android.app.Activity
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.PaymentSessionDto
import com.renjo.app.data.network.dto.WalletPayloadDto
import com.renjo.app.data.network.dto.WalletTopUpVerifyRequest
import com.renjo.app.data.payment.CashfreeLauncher
import com.renjo.app.data.payment.GatewayCallbackEvent
import com.renjo.app.data.payment.RazorpayLauncher
import com.renjo.app.data.repository.WalletRepository
import com.renjo.app.data.session.SessionManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface WalletUiState {
    data object Loading : WalletUiState
    data class Loaded(val wallet: WalletPayloadDto) : WalletUiState
    data class Error(val message: String) : WalletUiState
}

sealed interface TopUpState {
    data object Idle : TopUpState
    data object Starting : TopUpState
    data class RequiresPayment(val session: PaymentSessionDto) : TopUpState
    data object Verifying : TopUpState
    data class Error(val message: String) : TopUpState
}

@HiltViewModel
class WalletViewModel @Inject constructor(
    private val walletRepository: WalletRepository,
    private val sessionManager: SessionManager,
    private val razorpayLauncher: RazorpayLauncher,
    private val cashfreeLauncher: CashfreeLauncher,
) : ViewModel() {

    private val _uiState = MutableStateFlow<WalletUiState>(WalletUiState.Loading)
    val uiState: StateFlow<WalletUiState> = _uiState.asStateFlow()

    private val _topUpState = MutableStateFlow<TopUpState>(TopUpState.Idle)
    val topUpState: StateFlow<TopUpState> = _topUpState.asStateFlow()

    init {
        refresh()
    }

    fun refresh() {
        _uiState.value = WalletUiState.Loading
        viewModelScope.launch {
            _uiState.value = when (val result = walletRepository.wallet()) {
                is NetworkResult.Success -> WalletUiState.Loaded(result.data)
                is NetworkResult.Error -> WalletUiState.Error(result.message)
            }
        }
    }

    /** Default gateway is whichever the account has enabled first — same
     * "razorpay is the safe default" convention used at checkout. */
    fun startTopUp(amount: Double, gateway: String) {
        _topUpState.value = TopUpState.Starting
        viewModelScope.launch {
            when (val result = walletRepository.topUp(amount, gateway)) {
                is NetworkResult.Success -> _topUpState.value = TopUpState.RequiresPayment(result.data)
                is NetworkResult.Error -> _topUpState.value = TopUpState.Error(result.message)
            }
        }
    }

    fun launchGateway(activity: Activity, session: PaymentSessionDto) {
        viewModelScope.launch {
            val event = when (session.gateway) {
                "cashfree" -> cashfreeLauncher.launch(activity, session)
                else -> razorpayLauncher.launch(activity, session, sessionManager.currentUser.value?.phone)
            }
            completeTopUp(session, event)
        }
    }

    fun verifyStripeTopUp(session: PaymentSessionDto, paymentIntentId: String) {
        viewModelScope.launch { finishVerify(session, paymentIntentId, "stripe") }
    }

    fun cancelTopUp() {
        _topUpState.value = TopUpState.Idle
    }

    private suspend fun completeTopUp(session: PaymentSessionDto, event: GatewayCallbackEvent) {
        _topUpState.value = TopUpState.Verifying
        val gateway = session.gateway ?: "razorpay"
        when (event) {
            is GatewayCallbackEvent.RazorpaySuccess -> finishVerify(session, event.paymentId, gateway, event.orderId, event.signature)
            is GatewayCallbackEvent.CashfreeSuccess -> finishVerify(session, event.orderId, gateway)
            is GatewayCallbackEvent.RazorpayError, is GatewayCallbackEvent.CashfreeError ->
                _topUpState.value = TopUpState.Error("Payment was not completed. Please try again.")
        }
    }

    private suspend fun finishVerify(
        session: PaymentSessionDto,
        paymentId: String,
        gateway: String,
        razorpayOrderId: String? = null,
        razorpaySignature: String? = null,
    ) {
        val rechargeId = session.walletRechargeId ?: run {
            _topUpState.value = TopUpState.Error("Missing top-up reference.")
            return
        }
        val request = WalletTopUpVerifyRequest(
            walletRechargeId = rechargeId,
            paymentId = paymentId,
            paymentMethod = gateway,
            razorpayOrderId = razorpayOrderId,
            razorpaySignature = razorpaySignature,
            stripePaymentIntentId = if (gateway == "stripe") paymentId else null,
        )
        when (val result = walletRepository.verifyTopUp(request)) {
            is NetworkResult.Success -> {
                _uiState.value = WalletUiState.Loaded(result.data)
                _topUpState.value = TopUpState.Idle
            }
            is NetworkResult.Error -> _topUpState.value = TopUpState.Error(result.message)
        }
    }
}
