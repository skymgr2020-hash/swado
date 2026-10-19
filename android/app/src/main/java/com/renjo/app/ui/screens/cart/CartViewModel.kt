package com.renjo.app.ui.screens.cart

import androidx.lifecycle.ViewModel
import com.renjo.app.data.cart.CartRepository
import com.renjo.app.data.cart.RestaurantCart
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.StateFlow
import javax.inject.Inject

@HiltViewModel
class CartViewModel @Inject constructor(
    private val cartRepository: CartRepository,
) : ViewModel() {

    val cart: StateFlow<RestaurantCart?> = cartRepository.cart

    fun setQuantity(menuItemId: Int, selectedAddOns: List<com.renjo.app.data.network.dto.AddOnDto>, quantity: Int) {
        cartRepository.updateQuantity(menuItemId, selectedAddOns, quantity)
    }

    fun clear() = cartRepository.clear()
}
