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
class ResetPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val phone: String = checkNotNull(savedStateHandle["phone"])
    val verifiedPhoneToken: String = checkNotNull(savedStateHandle["token"])

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    fun resetPassword(password: String, confirmPassword: String, onDone: () -> Unit) {
        if (password.length < 6) {
            _uiState.value = AuthUiState.Error("Password must be at least 6 characters.")
            return
        }
        if (password != confirmPassword) {
            _uiState.value = AuthUiState.Error("Passwords don't match.")
            return
        }
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            when (val result = authRepository.resetPasswordByPhone(phone, verifiedPhoneToken, password)) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Success
                    onDone()
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }
}
