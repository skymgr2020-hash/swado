package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

@Serializable
data class CelebrationTypeEnvelope(val success: Boolean, val data: List<CelebrationTypeDto> = emptyList())

@Serializable
data class CelebrationTypeDto(val id: Int, val name: String, val icon: String? = null)

@Serializable
data class DiningRestaurantListEnvelope(val success: Boolean, val data: List<RestaurantDto> = emptyList())

@Serializable
data class BookTableRequest(
    val restaurantId: Int,
    val bookingDate: String,
    val bookingTime: String,
    val numberOfGuests: Int,
    val celebrationType: String? = null,
    val specialRequests: String? = null,
)

@Serializable
data class DiningBookingEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: DiningBookingDto? = null,
)

@Serializable
data class DiningBookingListEnvelope(
    val success: Boolean,
    val data: List<DiningBookingDto> = emptyList(),
)

@Serializable
data class DiningBookingDto(
    val id: Int,
    val bookingNumber: String? = null,
    val restaurantId: Int? = null,
    val restaurant: RestaurantDto? = null,
    val bookingDate: String? = null,
    val bookingTime: String? = null,
    val numberOfGuests: Int? = null,
    val celebrationType: String? = null,
    val specialRequests: String? = null,
    val status: String? = null,
    val bookingCharge: Double? = null,
    val paymentStatus: String? = null,
    val rating: Int? = null,
)

@Serializable
data class CancelBookingRequest(val cancellationReason: String? = null)

@Serializable
data class BookingReviewRequest(val rating: Int, val feedback: String)
