package com.renjo.app.ui.screens.onboarding

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.local.AppPreferences
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BrandingDto
import com.renjo.app.data.repository.BrandingRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class OnboardingViewModel @Inject constructor(
    private val appPreferences: AppPreferences,
    private val brandingRepository: BrandingRepository,
) : ViewModel() {

    private val _branding = MutableStateFlow<BrandingDto?>(null)
    val branding: StateFlow<BrandingDto?> = _branding.asStateFlow()

    init {
        viewModelScope.launch {
            when (val result = brandingRepository.getBranding()) {
                is NetworkResult.Success -> _branding.value = result.data
                is NetworkResult.Error -> Unit // splash already showed the error path; fail quiet here
            }
        }
    }

    fun markSeen(onDone: () -> Unit) {
        viewModelScope.launch {
            appPreferences.setOnboardingSeen()
            onDone()
        }
    }
}
