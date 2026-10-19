package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.CancelPaymentRequest
import com.renjo.app.data.network.dto.PayOrderRequest
import com.renjo.app.data.network.dto.PaymentSessionDto
import com.renjo.app.data.network.dto.PaymentStatusDto
import com.renjo.app.data.network.dto.VerifyPaymentRequest
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface PaymentRepository {
    suspend fun payForOrder(orderId: Int, gateway: String?): NetworkResult<PaymentSessionDto>
    suspend fun verifyPayment(request: VerifyPaymentRequest): NetworkResult<Unit>
    suspend fun cancelPayment(orderId: Int, reason: String?): NetworkResult<Unit>
    suspend fun paymentStatus(orderId: Int): NetworkResult<PaymentStatusDto>
}

class PaymentRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : PaymentRepository {
    override suspend fun payForOrder(orderId: Int, gateway: String?): NetworkResult<PaymentSessionDto> = safeApiCall {
        val response = apiService.payOrder(orderId, PayOrderRequest(gateway))
        response.data ?: error(response.message ?: "Couldn't start payment.")
    }

    override suspend fun verifyPayment(request: VerifyPaymentRequest): NetworkResult<Unit> = safeApiCall {
        val response = apiService.verifyPayment(request)
        if (!response.success) error(response.message ?: "Payment verification failed.")
    }

    override suspend fun cancelPayment(orderId: Int, reason: String?): NetworkResult<Unit> = safeApiCall {
        apiService.cancelPayment(CancelPaymentRequest(orderId, reason))
        Unit
    }

    override suspend fun paymentStatus(orderId: Int): NetworkResult<PaymentStatusDto> = safeApiCall {
        val response = apiService.paymentStatus(orderId)
        response.data ?: error("Couldn't fetch payment status.")
    }
}
