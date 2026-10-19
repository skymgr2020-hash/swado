package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/** Shapes per AddressController — note the response envelope quirk: unlike
 * most endpoints ({success, data}), address mutations return {success,
 * message, address} directly (see docs/api-contract). */
@Serializable
data class AddressListEnvelope(
    val success: Boolean,
    val data: List<AddressDto> = emptyList(),
)

@Serializable
data class AddressMutationEnvelope(
    val success: Boolean,
    val message: String? = null,
    val address: AddressDto? = null,
)

@Serializable
data class AddressDto(
    val id: Int,
    val name: String? = null,
    val address: String,
    val city: String? = null,
    val state: String? = null,
    val pincode: String? = null,
    val phone: String? = null,
    val latitude: Double,
    val longitude: Double,
    val isDefault: Boolean = false,
    val distanceKm: Double? = null,
    val isDeliverable: Boolean? = null,
)

@Serializable
data class AddressRequest(
    val name: String? = null,
    val address: String,
    val city: String,
    val state: String,
    val pincode: String,
    val phone: String,
    val latitude: Double,
    val longitude: Double,
)
