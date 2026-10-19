package com.renjo.app.ui.screens.offers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.OfferDto
import com.renjo.app.data.repository.RestaurantRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface OffersUiState {
    data object Loading : OffersUiState
    data class Loaded(val offers: List<OfferDto>) : OffersUiState
    data class Error(val message: String) : OffersUiState
}

@HiltViewModel
class OffersViewModel @Inject constructor(
    private val restaurantRepository: RestaurantRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow<OffersUiState>(OffersUiState.Loading)
    val uiState: StateFlow<OffersUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            _uiState.value = when (val result = restaurantRepository.activeOffers()) {
                is NetworkResult.Success -> OffersUiState.Loaded(result.data)
                is NetworkResult.Error -> OffersUiState.Error(result.message)
            }
        }
    }
}
