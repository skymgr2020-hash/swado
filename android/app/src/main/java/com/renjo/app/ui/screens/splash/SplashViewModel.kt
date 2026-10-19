package com.renjo.app.ui.screens.splash

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BrandingDto
import com.renjo.app.data.repository.BrandingRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface SplashUiState {
    data object Loading : SplashUiState
    data class Loaded(val branding: BrandingDto) : SplashUiState
    data class Error(val message: String) : SplashUiState
}

/** Minimum splash display time, matching the Flutter app's
 * Future.wait([startupFuture, Future.delayed(5s)]) in app_splash_screen.dart. */
private const val MINIMUM_SPLASH_DURATION_MS = 5000L

@HiltViewModel
class SplashViewModel @Inject constructor(
    private val brandingRepository: BrandingRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow<SplashUiState>(SplashUiState.Loading)
    val uiState: StateFlow<SplashUiState> = _uiState.asStateFlow()

    init {
        loadBranding()
    }

    fun loadBranding() {
        _uiState.value = SplashUiState.Loading
        viewModelScope.launch {
            val brandingDeferred = async { brandingRepository.getBranding() }
            delay(MINIMUM_SPLASH_DURATION_MS)
            when (val result = brandingDeferred.await()) {
                is NetworkResult.Success -> _uiState.value = SplashUiState.Loaded(result.data)
                is NetworkResult.Error -> _uiState.value = SplashUiState.Error(result.message)
            }
        }
    }
}
