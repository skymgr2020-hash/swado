package com.renjo.app.ui.screens.restaurant

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.cart.CartRepository
import com.renjo.app.data.cart.RestaurantCart
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.AddOnDto
import com.renjo.app.data.network.dto.MenuItemDto
import com.renjo.app.data.network.dto.MenuResponseDto
import com.renjo.app.data.repository.MenuRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface MenuUiState {
    data object Loading : MenuUiState
    data class Loaded(val menu: MenuResponseDto) : MenuUiState
    data class Error(val message: String) : MenuUiState
}

@HiltViewModel
class RestaurantDetailViewModel @Inject constructor(
    private val menuRepository: MenuRepository,
    private val cartRepository: CartRepository,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val restaurantId: Int = checkNotNull(savedStateHandle["restaurantId"])

    private val _uiState = MutableStateFlow<MenuUiState>(MenuUiState.Loading)
    val uiState: StateFlow<MenuUiState> = _uiState.asStateFlow()

    val cart: StateFlow<RestaurantCart?> = cartRepository.cart

    init {
        load()
    }

    fun load() {
        _uiState.value = MenuUiState.Loading
        viewModelScope.launch {
            when (val result = menuRepository.menu(restaurantId)) {
                is NetworkResult.Success -> _uiState.value = MenuUiState.Loaded(result.data)
                is NetworkResult.Error -> _uiState.value = MenuUiState.Error(result.message)
            }
        }
    }

    fun addToCart(menuItem: MenuItemDto, quantity: Int = 1, selectedAddOns: List<AddOnDto> = emptyList()) {
        val restaurantName = (_uiState.value as? MenuUiState.Loaded)?.menu?.restaurant?.name ?: ""
        cartRepository.addItem(restaurantId, restaurantName, menuItem, quantity, selectedAddOns)
    }

    fun quantityOf(menuItemId: Int): Int = cartRepository.quantityOf(menuItemId)

    fun decrementSimple(menuItem: MenuItemDto) {
        val newQuantity = quantityOf(menuItem.id) - 1
        cartRepository.updateQuantity(menuItem.id, emptyList(), newQuantity)
    }
}
