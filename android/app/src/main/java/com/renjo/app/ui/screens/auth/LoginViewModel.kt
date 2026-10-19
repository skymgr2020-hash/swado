package com.renjo.app.ui.screens.auth

import android.app.Activity
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.auth.FirebasePhoneAuthService
import com.renjo.app.data.auth.PhoneAuthStartResult
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.repository.AuthRepository
import com.renjo.app.data.repository.BrandingRepository
import com.renjo.app.ui.navigation.AuthFlow
import com.renjo.app.util.normalizePhoneToE164
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

/**
 * Mirrors the Flutter app's single "Continue" button (lib/screens/auth/login_screen.dart
 * _continueWithPhone): one phone field decides login vs signup for you by
 * checking /auth/phone/status first, rather than separate login/signup entry
 * points. Firebase auto-verification (rare, e.g. test numbers) can complete
 * the whole flow without ever reaching the OTP screen.
 */
sealed interface LoginContinueResult {
    data class OtpRequired(val phone: String, val flow: String) : LoginContinueResult
    data class Completed(val outcome: OtpVerifyResult, val phone: String) : LoginContinueResult
}

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val brandingRepository: BrandingRepository,
    private val firebasePhoneAuthService: FirebasePhoneAuthService,
) : ViewModel() {

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    val showGoogleButton: StateFlow<Boolean> = brandingRepository.cached
        .map { it?.socialLogin?.enabled == true && it.socialLogin.providers?.google?.enabled == true }
        .stateIn(viewModelScope, kotlinx.coroutines.flow.SharingStarted.Eagerly, false)

    private val usesFirebase: Boolean
        get() = brandingRepository.cached.value?.otpServiceProvider == "firebase"

    fun continueWithPhone(rawPhone: String, activity: Activity?, onResult: (LoginContinueResult) -> Unit) {
        if (rawPhone.isBlank()) {
            _uiState.value = AuthUiState.Error("Enter your mobile number.")
            return
        }
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            val normalizedPhone = normalizePhoneToE164(rawPhone, brandingRepository.cached.value?.defaultMobileCountryCode)
            when (val statusResult = authRepository.phoneStatus(normalizedPhone)) {
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(statusResult.message)
                is NetworkResult.Success -> {
                    val status = statusResult.data
                    if (status.exists && status.matchesRole == false) {
                        _uiState.value = AuthUiState.Error("This mobile number is not registered for a customer account.")
                        return@launch
                    }
                    val flow = if (status.exists) AuthFlow.LOGIN else AuthFlow.SIGNUP
                    sendOtpFor(normalizedPhone, flow, activity, onResult)
                }
            }
        }
    }

    private suspend fun sendOtpFor(phone: String, flow: String, activity: Activity?, onResult: (LoginContinueResult) -> Unit) {
        if (usesFirebase) {
            if (activity == null) {
                _uiState.value = AuthUiState.Error("Couldn't start verification. Please try again.")
                return
            }
            when (val result = firebasePhoneAuthService.startVerification(activity, phone)) {
                is PhoneAuthStartResult.CodeSent -> {
                    _uiState.value = AuthUiState.Idle
                    onResult(LoginContinueResult.OtpRequired(phone, flow))
                }
                is PhoneAuthStartResult.AutoVerified -> completeWithFirebaseToken(phone, flow, result.idToken, onResult)
                is PhoneAuthStartResult.Failed -> _uiState.value = AuthUiState.Error(result.message)
            }
        } else {
            when (val result = authRepository.sendOtp(phone, flow)) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Idle
                    onResult(LoginContinueResult.OtpRequired(phone, flow))
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }

    private suspend fun completeWithFirebaseToken(phone: String, flow: String, idToken: String, onResult: (LoginContinueResult) -> Unit) {
        if (flow == AuthFlow.LOGIN) {
            when (val result = authRepository.loginWithPhone(phone, idToken)) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Success
                    onResult(LoginContinueResult.Completed(OtpVerifyResult.LoggedIn, phone))
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        } else {
            when (val result = authRepository.verifyFirebasePhone(phone, idToken, flow)) {
                is NetworkResult.Success -> {
                    val token = result.data.verifiedPhoneToken
                    if (token == null) {
                        _uiState.value = AuthUiState.Error("Unexpected response from server.")
                        return
                    }
                    _uiState.value = AuthUiState.Success
                    onResult(LoginContinueResult.Completed(OtpVerifyResult.ReadyToRegister(token), phone))
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }

    fun clearError() {
        if (_uiState.value is AuthUiState.Error) _uiState.value = AuthUiState.Idle
    }
}
