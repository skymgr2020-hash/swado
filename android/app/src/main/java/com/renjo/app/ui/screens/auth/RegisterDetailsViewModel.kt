package com.renjo.app.ui.screens.auth

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterDetailsViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val phone: String = checkNotNull(savedStateHandle["phone"])
    val verifiedPhoneToken: String = checkNotNull(savedStateHandle["token"])

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    fun register(name: String, email: String, onRegistered: () -> Unit) {
        if (name.isBlank()) {
            _uiState.value = AuthUiState.Error("Enter your name.")
            return
        }
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            val result = authRepository.register(
                name = name.trim(),
                email = email.trim().ifBlank { null },
                phone = phone,
                verifiedPhoneToken = verifiedPhoneToken,
                referralCode = null,
            )
            when (result) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Success
                    onRegistered()
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }
}
