package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

/**
 * Shapes per HomeSectionService (admin/app/Services/HomeSectionService.php)
 * via ContentController::homeSections, transcribed from a real capture
 * against swado.online (2026-08-17) — this is the backend's "Home Section
 * Management" CMS: an admin-ordered list of section slots for the home feed.
 *
 * Known quirk: `restaurant_discovery` sections ALWAYS have empty `items`
 * (HomeSectionService.php:270-278 hardcodes `collect()`) — it's a marker
 * telling the client "render the restaurant grid here", not actual data.
 * The client cross-fetches `/restaurants/nearby` separately and slots it in
 * at that section's position; per-item shapes otherwise vary by `type`
 * (categories = cuisine chips, deals_for_you = promotion objects), so
 * `items` stays raw JSON and is decoded per-type on demand.
 */
@Serializable
data class HomeSectionEnvelope(
    val success: Boolean,
    val data: List<HomeSectionDto> = emptyList(),
)

@Serializable
data class HomeSectionDto(
    val token: String,
    val type: String,
    val title: String? = null,
    val subtitle: String? = null,
    val items: List<JsonElement> = emptyList(),
)

@Serializable
data class CuisineItemDto(
    val id: Int,
    val cuisineId: Int? = null,
    val name: String,
    val icon: String? = null,
)

@Serializable
data class DealItemDto(
    val id: Int,
    val title: String,
    val description: String? = null,
    val discountType: String? = null,
    val discountValue: Double? = null,
    val imageUrl: String? = null,
)
