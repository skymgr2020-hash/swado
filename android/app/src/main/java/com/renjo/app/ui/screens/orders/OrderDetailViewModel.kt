package com.renjo.app.ui.screens.orders

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.OrderFeedbackRequest
import com.renjo.app.data.network.dto.OrderSummaryListItemDto
import com.renjo.app.data.network.dto.OrderTrackDto
import com.renjo.app.data.repository.OrderRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface OrderDetailUiState {
    data object Loading : OrderDetailUiState
    data class Loaded(val order: OrderSummaryListItemDto, val track: OrderTrackDto?) : OrderDetailUiState
    data class Error(val message: String) : OrderDetailUiState
}

private val activeStatuses = setOf("pending", "confirmed", "preparing", "ready_for_pickup", "reached_pickup", "picked_up", "on_the_way")

@HiltViewModel
class OrderDetailViewModel @Inject constructor(
    private val orderRepository: OrderRepository,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    private val orderId: Int = checkNotNull(savedStateHandle["orderId"])

    private val _uiState = MutableStateFlow<OrderDetailUiState>(OrderDetailUiState.Loading)
    val uiState: StateFlow<OrderDetailUiState> = _uiState.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    init {
        load()
    }

    /** No push event exists for live driver location (backend constraint —
     * see docs/api-contract research) so tracking is polled every 8s while
     * the order is still active, mirroring the Flutter app's fallback timer. */
    private fun load() {
        viewModelScope.launch {
            while (true) {
                val orderResult = orderRepository.orderDetail(orderId)
                when (orderResult) {
                    is NetworkResult.Success -> {
                        val order = orderResult.data
                        val track = if (order.status in activeStatuses) {
                            (orderRepository.trackOrder(orderId) as? NetworkResult.Success)?.data
                        } else {
                            null
                        }
                        _uiState.value = OrderDetailUiState.Loaded(order, track)
                        if (order.status !in activeStatuses) return@launch
                    }
                    is NetworkResult.Error -> {
                        _uiState.value = OrderDetailUiState.Error(orderResult.message)
                        return@launch
                    }
                }
                delay(8000)
            }
        }
    }

    fun cancelOrder(reason: String) {
        viewModelScope.launch {
            when (val result = orderRepository.cancelOrder(orderId, reason)) {
                is NetworkResult.Success -> {
                    _actionMessage.value = "Order cancelled."
                    load()
                }
                is NetworkResult.Error -> _actionMessage.value = result.message
            }
        }
    }

    fun submitFeedback(restaurantRating: Int, feedback: String?) {
        viewModelScope.launch {
            when (val result = orderRepository.submitFeedback(orderId, OrderFeedbackRequest(restaurantRating, restaurantFeedback = feedback))) {
                is NetworkResult.Success -> _actionMessage.value = "Thanks for your feedback!"
                is NetworkResult.Error -> _actionMessage.value = result.message
            }
        }
    }

    fun tip(amount: Double) {
        viewModelScope.launch {
            when (val result = orderRepository.tipOrder(orderId, amount)) {
                is NetworkResult.Success -> _actionMessage.value = "Tip sent — thank you!"
                is NetworkResult.Error -> _actionMessage.value = result.message
            }
        }
    }

    fun dismissActionMessage() {
        _actionMessage.value = null
    }
}
