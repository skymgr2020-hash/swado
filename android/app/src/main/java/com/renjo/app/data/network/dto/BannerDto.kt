package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/** Shapes per ContentController::banners, real capture 2026-08-17 against
 * swado.online — a DIFFERENT endpoint from /home/sections' "banner_carousel"
 * section type (which isn't currently configured on production); this is
 * the one that actually has content right now. */
@Serializable
data class BannerListEnvelope(
    val success: Boolean,
    val data: List<BannerDto> = emptyList(),
)

@Serializable
data class BannerDto(
    val id: Int,
    val title: String? = null,
    val description: String? = null,
    val ctaLabel: String? = null,
    val imageUrl: String? = null,
    val link: String? = null,
)
