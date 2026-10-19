package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

/**
 * Shapes per MenuController::index (MenuItemResource), real capture
 * 2026-08-17 against swado.online restaurant id 1. Use the flat
 * `menu_items` array, not `categories[].menuItems` — different, unsynced
 * shape (see docs/api-contract).
 *
 * Known quirk: price/discountedPrice/rating are decimal-cast fields that
 * serialize as STRINGS ("199.00000", "0.0"), not numbers — kept as String?
 * here and parsed defensively via the effectivePrice/basePrice/ratingValue
 * helpers rather than declaring them Double and risking a decode crash.
 */
@Serializable
data class MenuResponseEnvelope(
    val success: Boolean,
    val data: MenuResponseDto,
)

@Serializable
data class MenuResponseDto(
    val restaurant: MenuRestaurantDto,
    val menuItems: List<MenuItemDto> = emptyList(),
)

@Serializable
data class MenuRestaurantDto(
    val id: Int,
    val name: String,
    val isOpen: Boolean = false,
)

@Serializable
data class MenuItemEnvelope(
    val success: Boolean,
    val data: MenuItemDto,
)

@Serializable
data class MenuItemDto(
    val id: Int,
    val restaurantId: Int,
    val categoryId: Int? = null,
    val categoryName: String? = null,
    val name: String,
    val description: String? = null,
    val price: String? = null,
    val discountedPrice: String? = null,
    val finalPrice: Double? = null,
    val imageUrl: String? = null,
    val isVeg: Boolean = false,
    val isAvailable: Boolean = true,
    val isBestseller: Boolean = false,
    val isRecommended: Boolean = false,
    val isSpicy: Boolean = false,
    val variants: List<JsonElement> = emptyList(),
    val addOns: List<AddOnDto> = emptyList(),
    val preparationTime: Int? = null,
    val rating: String? = null,
    val totalOrders: Int? = null,
) {
    val basePrice: Double get() = price?.toDoubleOrNull() ?: 0.0
    val effectivePrice: Double get() = finalPrice ?: discountedPrice?.toDoubleOrNull() ?: basePrice
    val ratingValue: Double get() = rating?.toDoubleOrNull() ?: 0.0
    val hasDiscount: Boolean get() = discountedPrice?.toDoubleOrNull()?.let { it < basePrice } == true
}

@Serializable
data class AddOnDto(
    val name: String,
    val price: Double = 0.0,
    val isAvailable: Boolean = true,
)
