package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

@Serializable
data class OfferListEnvelope(val success: Boolean, val data: List<OfferDto> = emptyList())

@Serializable
data class OfferDto(
    val id: Int? = null,
    val title: String? = null,
    val name: String? = null,
    val description: String? = null,
    val image: String? = null,
    val imageUrl: String? = null,
    val discountDetails: String? = null,
    val couponCode: String? = null,
)
