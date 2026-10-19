package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/**
 * Shape captured live from GET /api/app/branding against the real Laravel backend
 * (admin/app/Http/Controllers/Api/AuthController.php::branding), not inferred from
 * source alone. Field names are snake_case on the wire; NetworkModule configures
 * JsonNamingStrategy.SnakeCase so these stay camelCase in Kotlin.
 */
@Serializable
data class BrandingEnvelope(
    val success: Boolean,
    val data: BrandingDto,
)

@Serializable
data class BrandingDto(
    val appName: String,
    val appLogo: String? = null,
    val appIcon: String? = null,
    val appFavicon: String? = null,
    val frontendBackgroundImage: String? = null,
    val primaryColor: String? = null,
    val secondaryColor: String? = null,
    val restaurantPrimaryColor: String? = null,
    val restaurantSecondaryColor: String? = null,
    val driverPrimaryColor: String? = null,
    val driverSecondaryColor: String? = null,
    val customerPlayStoreUrl: String? = null,
    val customerDeeplinkScheme: String? = null,
    val customerDeeplinkBaseUrl: String? = null,
    val customerOrderDeeplinkTemplate: String? = null,
    val customerRestaurantDeeplinkTemplate: String? = null,
    val customerWalletDeeplinkTemplate: String? = null,
    val vendorMode: String? = null,
    val singleRestaurantId: Int? = null,
    val singleRestaurantName: String? = null,
    val appReleases: Map<String, AppReleaseDto> = emptyMap(),
    val supportEmail: String? = null,
    val supportPhone: String? = null,
    val defaultMobileCountryCode: String? = null,
    val otpServiceProvider: String? = null,
    val codEnabled: Boolean = true,
    val socialLogin: SocialLoginDto? = null,
    val pusherAppKey: String? = null,
    val pusherAppCluster: String? = null,
    val headerBrandingType: String? = null,
    val onboardingIntroTitle: String? = null,
    val onboardingIntroSubtitle: String? = null,
    val onboardingSlides: List<OnboardingSlideDto> = emptyList(),
)

@Serializable
data class AppReleaseDto(
    val latestVersion: String = "",
    val latestBuildNumber: Int = 0,
    val minSupportedBuildNumber: Int = 0,
    val forceUpdate: Boolean = false,
    val androidUpdateUrl: String = "",
    val iosUpdateUrl: String = "",
    val releaseNotes: String = "",
)

@Serializable
data class SocialLoginDto(
    val enabled: Boolean = false,
    val autoRegister: Boolean = true,
    val autoLinkVerifiedEmail: Boolean = true,
    val providers: SocialProvidersDto? = null,
)

@Serializable
data class SocialProvidersDto(
    val google: SocialProviderDto? = null,
    val apple: SocialProviderDto? = null,
)

@Serializable
data class SocialProviderDto(
    val enabled: Boolean = false,
    val webClientId: String? = null,
    val servicesId: String? = null,
)

@Serializable
data class OnboardingSlideDto(
    val title: String,
    val description: String,
    val image: String,
)
