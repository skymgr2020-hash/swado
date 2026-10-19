package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BookTableRequest
import com.renjo.app.data.network.dto.BookingReviewRequest
import com.renjo.app.data.network.dto.CancelBookingRequest
import com.renjo.app.data.network.dto.CelebrationTypeDto
import com.renjo.app.data.network.dto.DiningBookingDto
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface DiningRepository {
    suspend fun celebrationTypes(): NetworkResult<List<CelebrationTypeDto>>
    suspend fun restaurants(lat: Double, lng: Double, radius: Double? = null): NetworkResult<List<RestaurantDto>>
    suspend fun book(request: BookTableRequest): NetworkResult<DiningBookingDto>
    suspend fun myBookings(): NetworkResult<List<DiningBookingDto>>
    suspend fun bookingDetails(id: Int): NetworkResult<DiningBookingDto>
    suspend fun cancel(id: Int, reason: String?): NetworkResult<Unit>
    suspend fun review(id: Int, rating: Int, feedback: String): NetworkResult<Unit>
}

class DiningRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : DiningRepository {
    override suspend fun celebrationTypes(): NetworkResult<List<CelebrationTypeDto>> = safeApiCall {
        apiService.celebrationTypes().data
    }

    override suspend fun restaurants(lat: Double, lng: Double, radius: Double?): NetworkResult<List<RestaurantDto>> = safeApiCall {
        apiService.diningRestaurants(lat, lng, radius).data
    }

    override suspend fun book(request: BookTableRequest): NetworkResult<DiningBookingDto> = safeApiCall {
        val response = apiService.bookTable(request)
        response.data ?: error(response.message ?: "Couldn't book table.")
    }

    override suspend fun myBookings(): NetworkResult<List<DiningBookingDto>> = safeApiCall {
        apiService.myBookings().data
    }

    override suspend fun bookingDetails(id: Int): NetworkResult<DiningBookingDto> = safeApiCall {
        val response = apiService.bookingDetails(id)
        response.data ?: error(response.message ?: "Couldn't load booking.")
    }

    override suspend fun cancel(id: Int, reason: String?): NetworkResult<Unit> = safeApiCall {
        apiService.cancelBooking(id, CancelBookingRequest(reason))
        Unit
    }

    override suspend fun review(id: Int, rating: Int, feedback: String): NetworkResult<Unit> = safeApiCall {
        apiService.submitBookingReview(id, BookingReviewRequest(rating, feedback))
        Unit
    }
}
