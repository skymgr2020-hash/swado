package com.renjo.app.data.cart

import com.renjo.app.data.network.dto.AddOnDto
import com.renjo.app.data.network.dto.MenuItemDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

data class CartLineItem(
    val menuItemId: Int,
    val name: String,
    val unitPrice: Double,
    val quantity: Int,
    val imageUrl: String?,
    val selectedAddOns: List<AddOnDto> = emptyList(),
) {
    val lineTotal: Double get() = (unitPrice + selectedAddOns.sumOf { it.price }) * quantity
}

data class RestaurantCart(
    val restaurantId: Int,
    val restaurantName: String,
    val items: List<CartLineItem> = emptyList(),
) {
    val subtotal: Double get() = items.sumOf { it.lineTotal }
    val totalQuantity: Int get() = items.sumOf { it.quantity }
}

/**
 * Cart is 100% client-side — there is no server cart endpoint (see
 * docs/api-contract/cart-orders.md, written once Phase 4 lands). Shaped so
 * each line item maps directly onto what POST /orders/summary and
 * POST /orders expect: {id, quantity, selected_add_ons}. Adding an item from
 * a different restaurant than what's already in the cart replaces it,
 * matching the Flutter app's CartProvider behavior.
 */
@Singleton
class CartRepository @Inject constructor() {
    private val _cart = MutableStateFlow<RestaurantCart?>(null)
    val cart: StateFlow<RestaurantCart?> = _cart.asStateFlow()

    fun addItem(restaurantId: Int, restaurantName: String, menuItem: MenuItemDto, quantity: Int, selectedAddOns: List<AddOnDto>) {
        val current = _cart.value
        val base = if (current == null || current.restaurantId != restaurantId) {
            RestaurantCart(restaurantId, restaurantName)
        } else {
            current
        }
        val existingIndex = base.items.indexOfFirst { it.menuItemId == menuItem.id && it.selectedAddOns == selectedAddOns }
        val newItems = if (existingIndex >= 0) {
            base.items.toMutableList().also {
                it[existingIndex] = it[existingIndex].copy(quantity = it[existingIndex].quantity + quantity)
            }
        } else {
            base.items + CartLineItem(menuItem.id, menuItem.name, menuItem.effectivePrice, quantity, menuItem.imageUrl, selectedAddOns)
        }
        _cart.value = base.copy(items = newItems)
    }

    fun updateQuantity(menuItemId: Int, selectedAddOns: List<AddOnDto>, quantity: Int) {
        val current = _cart.value ?: return
        val newItems = if (quantity <= 0) {
            current.items.filterNot { it.menuItemId == menuItemId && it.selectedAddOns == selectedAddOns }
        } else {
            current.items.map {
                if (it.menuItemId == menuItemId && it.selectedAddOns == selectedAddOns) it.copy(quantity = quantity) else it
            }
        }
        _cart.value = if (newItems.isEmpty()) null else current.copy(items = newItems)
    }

    fun quantityOf(menuItemId: Int): Int =
        _cart.value?.items?.filter { it.menuItemId == menuItemId }?.sumOf { it.quantity } ?: 0

    fun clear() {
        _cart.value = null
    }
}
