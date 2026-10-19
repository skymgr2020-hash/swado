package com.renjo.app.ui.screens.auth

import android.app.Activity
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.auth.FirebasePhoneAuthService
import com.renjo.app.data.auth.PhoneAuthStartResult
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.repository.AuthRepository
import com.renjo.app.data.repository.BrandingRepository
import com.renjo.app.ui.navigation.AuthFlow
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface OtpVerifyResult {
    data object LoggedIn : OtpVerifyResult
    data class ReadyToRegister(val verifiedPhoneToken: String) : OtpVerifyResult
    data class ReadyToResetPassword(val verifiedPhoneToken: String) : OtpVerifyResult
}

/** Matches the Flutter app's 28-second resend cooldown. */
private const val RESEND_COOLDOWN_SECONDS = 28

@HiltViewModel
class OtpVerifyViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val brandingRepository: BrandingRepository,
    private val firebasePhoneAuthService: FirebasePhoneAuthService,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val phone: String = checkNotNull(savedStateHandle["phone"])
    val flow: String = checkNotNull(savedStateHandle["flow"])

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    private val _secondsRemaining = MutableStateFlow(RESEND_COOLDOWN_SECONDS)
    val secondsRemaining: StateFlow<Int> = _secondsRemaining.asStateFlow()

    val usesFirebase: Boolean
        get() = brandingRepository.cached.value?.otpServiceProvider == "firebase"

    init {
        startCountdown()
    }

    private fun startCountdown() {
        _secondsRemaining.value = RESEND_COOLDOWN_SECONDS
        viewModelScope.launch {
            while (_secondsRemaining.value > 0) {
                delay(1000)
                _secondsRemaining.value -= 1
            }
        }
    }

    fun verify(code: String, onResult: (OtpVerifyResult) -> Unit) {
        if (code.isBlank()) {
            _uiState.value = AuthUiState.Error("Enter the code we sent you.")
            return
        }
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            if (usesFirebase) {
                verifyWithFirebase(code, onResult)
            } else {
                verifyWithBackendOtp(code, onResult)
            }
        }
    }

    private suspend fun verifyWithBackendOtp(code: String, onResult: (OtpVerifyResult) -> Unit) {
        when (val result = authRepository.verifyOtp(phone, code.trim(), flow)) {
            is NetworkResult.Success -> {
                _uiState.value = AuthUiState.Success
                val data = result.data
                when {
                    flow == AuthFlow.LOGIN && data.token != null -> onResult(OtpVerifyResult.LoggedIn)
                    flow == AuthFlow.SIGNUP && data.verifiedPhoneToken != null ->
                        onResult(OtpVerifyResult.ReadyToRegister(data.verifiedPhoneToken))
                    flow == AuthFlow.FORGOT_PASSWORD && data.verifiedPhoneToken != null ->
                        onResult(OtpVerifyResult.ReadyToResetPassword(data.verifiedPhoneToken))
                    else -> _uiState.value = AuthUiState.Error("Unexpected response from server.")
                }
            }
            is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
        }
    }

    private suspend fun verifyWithFirebase(code: String, onResult: (OtpVerifyResult) -> Unit) {
        val idToken = try {
            firebasePhoneAuthService.confirmCode(code.trim())
        } catch (e: Exception) {
            _uiState.value = AuthUiState.Error(e.message ?: "Invalid or expired code.")
            return
        }
        when (flow) {
            AuthFlow.LOGIN -> when (val result = authRepository.loginWithPhone(phone, idToken)) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Success
                    onResult(OtpVerifyResult.LoggedIn)
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
            else -> when (val result = authRepository.verifyFirebasePhone(phone, idToken, flow)) {
                is NetworkResult.Success -> {
                    val token = result.data.verifiedPhoneToken
                    if (token == null) {
                        _uiState.value = AuthUiState.Error("Unexpected response from server.")
                        return
                    }
                    _uiState.value = AuthUiState.Success
                    val outcome = if (flow == AuthFlow.SIGNUP) {
                        OtpVerifyResult.ReadyToRegister(token)
                    } else {
                        OtpVerifyResult.ReadyToResetPassword(token)
                    }
                    onResult(outcome)
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }

    fun resend(activity: Activity?) {
        if (_secondsRemaining.value > 0) return
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            if (usesFirebase) {
                if (activity == null) {
                    _uiState.value = AuthUiState.Error("Couldn't resend. Please try again.")
                    return@launch
                }
                when (val result = firebasePhoneAuthService.startVerification(activity, phone)) {
                    is PhoneAuthStartResult.CodeSent -> {
                        _uiState.value = AuthUiState.Idle
                        startCountdown()
                    }
                    is PhoneAuthStartResult.AutoVerified -> {
                        // Rare on resend, but handle it the same way verify() would.
                        _uiState.value = AuthUiState.Idle
                        startCountdown()
                    }
                    is PhoneAuthStartResult.Failed -> _uiState.value = AuthUiState.Error(result.message)
                }
            } else {
                when (val result = authRepository.sendOtp(phone, flow)) {
                    is NetworkResult.Success -> {
                        _uiState.value = AuthUiState.Idle
                        startCountdown()
                    }
                    is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
                }
            }
        }
    }
}
