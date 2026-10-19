package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.MenuResponseDto
import com.renjo.app.data.network.safeApiCall
import javax.inject.Inject

interface MenuRepository {
    suspend fun menu(restaurantId: Int): NetworkResult<MenuResponseDto>
}

class MenuRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
) : MenuRepository {
    override suspend fun menu(restaurantId: Int): NetworkResult<MenuResponseDto> = safeApiCall {
        apiService.menu(restaurantId).data
    }
}
