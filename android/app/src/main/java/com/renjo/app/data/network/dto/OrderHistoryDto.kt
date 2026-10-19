package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/** Phase 6: order history/detail/tracking. GET /orders is a paginated
 * envelope; GET /orders/{id} and /orders/{id}/track are single-object. */

@Serializable
data class OrderListEnvelope(
    val success: Boolean,
    val data: OrderListPageDto? = null,
)

/** GET /orders wraps a real Laravel paginator object under `data` (not a
 * bare array) — confirmed from a live device capture:
 * {"success":true,"data":{"current_page":1,"data":[{"id":...}]}}. */
@Serializable
data class OrderListPageDto(
    val currentPage: Int? = null,
    val lastPage: Int? = null,
    val total: Int? = null,
    val data: List<OrderSummaryListItemDto> = emptyList(),
)

@Serializable
data class OrderDetailEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: OrderSummaryListItemDto? = null,
)

@Serializable
data class OrderSummaryListItemDto(
    val id: Int,
    val orderNumber: String? = null,
    val status: String? = null,
    val orderType: String? = null,
    val restaurantId: Int? = null,
    val restaurantName: String? = null,
    val subtotal: Double? = null,
    val deliveryFee: Double? = null,
    val platformFee: Double? = null,
    val tax: Double? = null,
    val discount: Double? = null,
    val total: Double? = null,
    val paymentMethod: String? = null,
    val paymentStatus: String? = null,
    val deliveryOtp: String? = null,
    val createdAt: String? = null,
    val estimatedDeliveryLabel: String? = null,
    val estimatedDeliveryMinutes: Int? = null,
    val deliveryDistanceKm: Double? = null,
    val rewardPointsEarned: Int? = null,
)

@Serializable
data class OrderTrackEnvelope(
    val success: Boolean,
    val data: OrderTrackDto? = null,
)

@Serializable
data class OrderTrackDto(
    val order: OrderTrackOrderDto? = null,
    val driverLocation: DriverLocationDto? = null,
    val isTakeaway: Boolean = false,
    val pickupLocation: PickupLocationDto? = null,
    val statusText: String? = null,
    val estimatedDeliveryTime: String? = null,
    val estimatedDeliveryLabel: String? = null,
    val estimatedDeliveryMinutes: Int? = null,
    val deliveryDistanceKm: Double? = null,
    val eta: OrderEtaDetailDto? = null,
)

@Serializable
data class OrderTrackOrderDto(
    val id: Int,
    val orderNumber: String? = null,
    val status: String? = null,
    val orderType: String? = null,
    val restaurantId: Int? = null,
    val driverId: Int? = null,
    val deliveryLat: Double? = null,
    val deliveryLng: Double? = null,
)

@Serializable
data class DriverLocationDto(
    val lat: Double? = null,
    val lng: Double? = null,
    val updatedAt: String? = null,
)

@Serializable
data class PickupLocationDto(
    val name: String? = null,
    val address: String? = null,
    val latitude: Double? = null,
    val longitude: Double? = null,
)

@Serializable
data class OrderEtaDetailDto(
    val preparationMinutes: Int? = null,
    val travelMinutes: Int? = null,
    val travelDistanceKm: Double? = null,
    val etaMinutes: Int? = null,
    val etaRange: String? = null,
)

@Serializable
data class CancelOrderRequest(val reason: String)

@Serializable
data class OrderFeedbackRequest(
    val restaurantRating: Int,
    val driverRating: Int? = null,
    val itemRating: Int? = null,
    val serviceRating: Int? = null,
    val restaurantFeedback: String? = null,
    val driverFeedback: String? = null,
)

@Serializable
data class OrderTipRequest(val amount: Double)
