package com.renjo.app.ui.screens.dining

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.location.LocationProvider
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BookTableRequest
import com.renjo.app.data.network.dto.DiningBookingDto
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.data.repository.DiningRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface DiningListUiState {
    data object Loading : DiningListUiState
    data class Loaded(val bookings: List<DiningBookingDto>) : DiningListUiState
    data class Error(val message: String) : DiningListUiState
}

sealed interface BookTableUiState {
    data object Idle : BookTableUiState
    data object LoadingRestaurants : BookTableUiState
    data class RestaurantsReady(val restaurants: List<RestaurantDto>) : BookTableUiState
    data object Booking : BookTableUiState
    data class Success(val booking: DiningBookingDto) : BookTableUiState
    data class Error(val message: String) : BookTableUiState
}

@HiltViewModel
class DiningViewModel @Inject constructor(
    private val diningRepository: DiningRepository,
    private val locationProvider: LocationProvider,
) : ViewModel() {

    private val _listState = MutableStateFlow<DiningListUiState>(DiningListUiState.Loading)
    val listState: StateFlow<DiningListUiState> = _listState.asStateFlow()

    private val _bookState = MutableStateFlow<BookTableUiState>(BookTableUiState.Idle)
    val bookState: StateFlow<BookTableUiState> = _bookState.asStateFlow()

    init {
        refreshBookings()
    }

    fun refreshBookings() {
        _listState.value = DiningListUiState.Loading
        viewModelScope.launch {
            _listState.value = when (val result = diningRepository.myBookings()) {
                is NetworkResult.Success -> DiningListUiState.Loaded(result.data)
                is NetworkResult.Error -> DiningListUiState.Error(result.message)
            }
        }
    }

    fun loadDiningRestaurants() {
        _bookState.value = BookTableUiState.LoadingRestaurants
        viewModelScope.launch {
            val location = locationProvider.getLastKnownLocation()
            if (location == null) {
                _bookState.value = BookTableUiState.Error("Choose a delivery location to find dining restaurants.")
                return@launch
            }
            _bookState.value = when (val result = diningRepository.restaurants(location.lat, location.lng)) {
                is NetworkResult.Success -> BookTableUiState.RestaurantsReady(result.data)
                is NetworkResult.Error -> BookTableUiState.Error(result.message)
            }
        }
    }

    fun bookTable(
        restaurantId: Int,
        date: String,
        time: String,
        guests: Int,
        celebrationType: String?,
        specialRequests: String?,
    ) {
        _bookState.value = BookTableUiState.Booking
        viewModelScope.launch {
            val request = BookTableRequest(restaurantId, date, time, guests, celebrationType, specialRequests)
            _bookState.value = when (val result = diningRepository.book(request)) {
                is NetworkResult.Success -> BookTableUiState.Success(result.data)
                is NetworkResult.Error -> BookTableUiState.Error(result.message)
            }
        }
    }
}
