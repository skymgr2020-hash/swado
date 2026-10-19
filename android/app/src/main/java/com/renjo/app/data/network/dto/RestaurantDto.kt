package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

/**
 * Shapes per RestaurantResource (admin/app/Http/Resources/RestaurantResource.php)
 * and RestaurantController::nearby, transcribed from the API contract doc.
 * Money/rating fields serialize as native JSON numbers here (unlike
 * MenuItemResource's decimal-cast strings — see docs/api-contract).
 */

@Serializable
data class RestaurantListEnvelope(
    val success: Boolean,
    val data: List<RestaurantDto> = emptyList(),
)

@Serializable
data class RestaurantDto(
    val id: Int,
    val name: String,
    val slug: String? = null,
    val description: String? = null,
    val address: String? = null,
    val city: String? = null,
    val cuisine: List<String> = emptyList(),
    val cuisineText: String? = null,
    val isOpen: Boolean = false,
    val isOpenNow: Boolean = false,
    val isPureVeg: Boolean = false,
    val minOrderAmount: Double? = null,
    val amountForOne: Double? = null,
    val deliveryFee: Double? = null,
    val deliveryTime: Int? = null,
    val restaurantType: String? = null,
    val rating: Double? = null,
    val totalRatings: Int? = null,
    val bannerImage: String? = null,
    val logoImage: String? = null,
    val distance: Double? = null,
    val etaMinutes: Int? = null,
    val etaRange: String? = null,
    val isFeatured: Boolean = false,
    // Deliberately loose: menu_items matched by search have a much richer,
    // differently-shaped payload (see docs/api-contract) that this screen
    // doesn't render — kept as raw JSON so decoding never breaks on it.
    val matchedMenuItems: List<JsonElement> = emptyList(),
)
