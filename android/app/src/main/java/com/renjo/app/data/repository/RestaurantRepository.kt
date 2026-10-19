package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BannerDto
import com.renjo.app.data.network.dto.HomeSectionDto
import com.renjo.app.data.network.dto.OfferDto
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface RestaurantRepository {
    suspend fun nearby(lat: Double, lng: Double, radiusKm: Double? = null): NetworkResult<List<RestaurantDto>>
    suspend fun homeSections(lat: Double, lng: Double, radiusKm: Double? = null): NetworkResult<List<HomeSectionDto>>
    suspend fun banners(): NetworkResult<List<BannerDto>>
    suspend fun activeOffers(): NetworkResult<List<OfferDto>>
}

class RestaurantRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : RestaurantRepository {
    override suspend fun nearby(lat: Double, lng: Double, radiusKm: Double?): NetworkResult<List<RestaurantDto>> = safeApiCall {
        apiService.nearbyRestaurants(lat, lng, radiusKm).data
    }

    override suspend fun homeSections(lat: Double, lng: Double, radiusKm: Double?): NetworkResult<List<HomeSectionDto>> = safeApiCall {
        apiService.homeSections(lat, lng, radiusKm).data
    }

    override suspend fun banners(): NetworkResult<List<BannerDto>> = safeApiCall {
        apiService.banners().data
    }

    override suspend fun activeOffers(): NetworkResult<List<OfferDto>> = safeApiCall {
        apiService.activeOffers().data
    }
}
