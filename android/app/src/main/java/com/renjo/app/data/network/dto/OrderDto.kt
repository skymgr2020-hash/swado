package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/** Shapes per OrderController::summary/store, per docs/api-contract
 * (Phase 0 research). The real response has 40+ pricing/promo fields —
 * only the ones the checkout UI actually renders are modeled here;
 * ignoreUnknownKeys handles the rest. Cart is 100% client-side: this is
 * called on every cart/coupon/address change to get the server's
 * authoritative price breakdown, never computed locally. */

@Serializable
data class OrderSummaryEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: OrderSummaryDto? = null,
)

@Serializable
data class OrderSummaryDto(
    val subtotal: Double = 0.0,
    val deliveryFee: Double = 0.0,
    val payableDeliveryFee: Double? = null,
    val platformFee: Double = 0.0,
    val tax: Double = 0.0,
    val taxLabel: String? = null,
    val discount: Double = 0.0,
    val total: Double = 0.0,
    val freeDeliveryEligible: Boolean = false,
    val rewardPointsEarned: Int? = null,
    val eta: OrderEtaDto? = null,
)

@Serializable
data class OrderEtaDto(
    val etaMinutes: Int? = null,
    val etaRange: String? = null,
)

@Serializable
data class OrderSummaryRequest(
    val restaurantId: Int,
    val items: List<OrderItemRequest>,
    val orderType: String = "delivery",
    val deliveryAddressId: Int? = null,
    val deliveryLat: Double? = null,
    val deliveryLng: Double? = null,
    val couponCode: String? = null,
)

@Serializable
data class OrderItemRequest(
    val id: Int,
    val quantity: Int,
    val selectedAddOns: List<AddOnDto>? = null,
)

@Serializable
data class PlaceOrderRequest(
    val restaurantId: Int,
    val items: List<OrderItemRequest>,
    val orderType: String = "delivery",
    val deliveryAddressId: Int? = null,
    val deliveryLat: Double? = null,
    val deliveryLng: Double? = null,
    val paymentMethod: String,
    val specialInstructions: String? = null,
    val couponCode: String? = null,
)

@Serializable
data class PlaceOrderEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: PlaceOrderDataDto? = null,
)

@Serializable
data class PlaceOrderDataDto(
    val order: OrderDto? = null,
    val orderNumber: String? = null,
    val total: Double? = null,
    val requiresPayment: Boolean = false,
)

@Serializable
data class OrderDto(
    val id: Int,
    val orderNumber: String? = null,
    val status: String? = null,
    val total: Double? = null,
    val paymentMethod: String? = null,
    val deliveryOtp: String? = null,
)
