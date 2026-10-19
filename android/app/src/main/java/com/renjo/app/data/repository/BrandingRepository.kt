package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BrandingDto
import com.renjo.app.data.network.safeApiCall
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

interface BrandingRepository {
    suspend fun getBranding(): NetworkResult<BrandingDto>

    /** Last successfully fetched branding — Splash fetches it first, so by
     * the time auth screens need it (e.g. to pick the phone-verification
     * path) it's already warm. */
    val cached: StateFlow<BrandingDto?>
}

@Singleton
class BrandingRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : BrandingRepository {

    private val _cached = MutableStateFlow<BrandingDto?>(null)
    override val cached: StateFlow<BrandingDto?> = _cached.asStateFlow()

    override suspend fun getBranding(): NetworkResult<BrandingDto> = safeApiCall {
        val branding = apiService.getBranding().data
        _cached.value = branding
        branding
    }
}
