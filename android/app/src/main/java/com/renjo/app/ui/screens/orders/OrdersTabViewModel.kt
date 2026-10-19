package com.renjo.app.ui.screens.orders

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.OrderSummaryListItemDto
import com.renjo.app.data.repository.OrderRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface OrdersUiState {
    data object Loading : OrdersUiState
    data class Loaded(val orders: List<OrderSummaryListItemDto>) : OrdersUiState
    data class Error(val message: String) : OrdersUiState
}

@HiltViewModel
class OrdersTabViewModel @Inject constructor(
    private val orderRepository: OrderRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow<OrdersUiState>(OrdersUiState.Loading)
    val uiState: StateFlow<OrdersUiState> = _uiState.asStateFlow()

    init {
        refresh()
    }

    fun refresh() {
        _uiState.value = OrdersUiState.Loading
        viewModelScope.launch {
            _uiState.value = when (val result = orderRepository.myOrders()) {
                is NetworkResult.Success -> OrdersUiState.Loaded(result.data)
                is NetworkResult.Error -> OrdersUiState.Error(result.message)
            }
        }
    }
}
