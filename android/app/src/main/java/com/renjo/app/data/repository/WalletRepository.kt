package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.PaymentSessionDto
import com.renjo.app.data.network.dto.WalletPayloadDto
import com.renjo.app.data.network.dto.WalletTopUpRequest
import com.renjo.app.data.network.dto.WalletTopUpVerifyRequest
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface WalletRepository {
    suspend fun wallet(): NetworkResult<WalletPayloadDto>
    suspend fun topUp(amount: Double, paymentMethod: String): NetworkResult<PaymentSessionDto>
    suspend fun verifyTopUp(request: WalletTopUpVerifyRequest): NetworkResult<WalletPayloadDto>
}

class WalletRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : WalletRepository {
    override suspend fun wallet(): NetworkResult<WalletPayloadDto> = safeApiCall {
        apiService.wallet().data ?: WalletPayloadDto()
    }

    override suspend fun topUp(amount: Double, paymentMethod: String): NetworkResult<PaymentSessionDto> = safeApiCall {
        val response = apiService.walletTopUp(WalletTopUpRequest(amount, paymentMethod, paymentMethod))
        response.data ?: error(response.message ?: "Couldn't start top-up.")
    }

    override suspend fun verifyTopUp(request: WalletTopUpVerifyRequest): NetworkResult<WalletPayloadDto> = safeApiCall {
        apiService.walletTopUpVerify(request).data ?: WalletPayloadDto()
    }
}
