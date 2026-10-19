package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.AddressDto
import com.renjo.app.data.network.dto.AddressRequest
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface AddressRepository {
    suspend fun list(restaurantId: Int? = null): NetworkResult<List<AddressDto>>
    suspend fun add(request: AddressRequest): NetworkResult<AddressDto>
    suspend fun delete(id: Int): NetworkResult<Unit>
}

class AddressRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : AddressRepository {
    override suspend fun list(restaurantId: Int?): NetworkResult<List<AddressDto>> = safeApiCall {
        apiService.addresses(restaurantId).data
    }

    override suspend fun add(request: AddressRequest): NetworkResult<AddressDto> = safeApiCall {
        val response = apiService.addAddress(request)
        response.address ?: error(response.message ?: "Couldn't save address.")
    }

    override suspend fun delete(id: Int): NetworkResult<Unit> = safeApiCall {
        apiService.deleteAddress(id)
        Unit
    }
}
