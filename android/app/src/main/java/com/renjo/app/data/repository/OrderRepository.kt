package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.CancelOrderRequest
import com.renjo.app.data.network.dto.OrderFeedbackRequest
import com.renjo.app.data.network.dto.OrderSummaryDto
import com.renjo.app.data.network.dto.OrderSummaryListItemDto
import com.renjo.app.data.network.dto.OrderSummaryRequest
import com.renjo.app.data.network.dto.OrderTipRequest
import com.renjo.app.data.network.dto.OrderTrackDto
import com.renjo.app.data.network.dto.PlaceOrderDataDto
import com.renjo.app.data.network.dto.PlaceOrderRequest
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface OrderRepository {
    suspend fun summary(request: OrderSummaryRequest): NetworkResult<OrderSummaryDto>
    suspend fun placeOrder(request: PlaceOrderRequest): NetworkResult<PlaceOrderDataDto>
    suspend fun myOrders(page: Int? = null): NetworkResult<List<OrderSummaryListItemDto>>
    suspend fun orderDetail(orderId: Int): NetworkResult<OrderSummaryListItemDto>
    suspend fun trackOrder(orderId: Int): NetworkResult<OrderTrackDto>
    suspend fun cancelOrder(orderId: Int, reason: String): NetworkResult<Unit>
    suspend fun submitFeedback(orderId: Int, request: OrderFeedbackRequest): NetworkResult<Unit>
    suspend fun tipOrder(orderId: Int, amount: Double): NetworkResult<Unit>
}

class OrderRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : OrderRepository {
    override suspend fun summary(request: OrderSummaryRequest): NetworkResult<OrderSummaryDto> = safeApiCall {
        val response = apiService.orderSummary(request)
        response.data ?: error(response.message ?: "Couldn't calculate pricing.")
    }

    override suspend fun placeOrder(request: PlaceOrderRequest): NetworkResult<PlaceOrderDataDto> = safeApiCall {
        val response = apiService.placeOrder(request)
        response.data ?: error(response.message ?: "Couldn't place order.")
    }

    override suspend fun myOrders(page: Int?): NetworkResult<List<OrderSummaryListItemDto>> = safeApiCall {
        apiService.myOrders(page).data?.data.orEmpty()
    }

    override suspend fun orderDetail(orderId: Int): NetworkResult<OrderSummaryListItemDto> = safeApiCall {
        val response = apiService.orderDetail(orderId)
        response.data ?: error(response.message ?: "Couldn't load order.")
    }

    override suspend fun trackOrder(orderId: Int): NetworkResult<OrderTrackDto> = safeApiCall {
        val response = apiService.trackOrder(orderId)
        response.data ?: error("Couldn't load tracking info.")
    }

    override suspend fun cancelOrder(orderId: Int, reason: String): NetworkResult<Unit> = safeApiCall {
        apiService.cancelOrder(orderId, CancelOrderRequest(reason))
        Unit
    }

    override suspend fun submitFeedback(orderId: Int, request: OrderFeedbackRequest): NetworkResult<Unit> = safeApiCall {
        apiService.submitOrderFeedback(orderId, request)
        Unit
    }

    override suspend fun tipOrder(orderId: Int, amount: Double): NetworkResult<Unit> = safeApiCall {
        apiService.tipOrder(orderId, OrderTipRequest(amount))
        Unit
    }
}
