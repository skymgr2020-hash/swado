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
import com.renjo.app.util.normalizePhoneToE164
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

/** What happens after tapping "Send OTP" — either a code was sent (proceed to
 * the verify screen) or Firebase auto-verified instantly (mainly test phone
 * numbers), in which case the flow is already complete. */
sealed interface PhoneEntryResult {
    data class CodeSent(val phone: String) : PhoneEntryResult
    data class Completed(val outcome: OtpVerifyResult, val phone: String) : PhoneEntryResult
}

@HiltViewModel
class PhoneEntryViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val brandingRepository: BrandingRepository,
    private val firebasePhoneAuthService: FirebasePhoneAuthService,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val flow: String = checkNotNull(savedStateHandle["flow"])

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    private val usesFirebase: Boolean
        get() = brandingRepository.cached.value?.otpServiceProvider == "firebase"

    /**
     * [activity] is required by the Firebase SDK for its reCAPTCHA fallback
     * flow; only used for the duration of this call, never retained.
     */
    fun sendOtp(phone: String, activity: Activity?, onResult: (PhoneEntryResult) -> Unit) {
        if (phone.isBlank()) {
            _uiState.value = AuthUiState.Error("Enter your phone number.")
            return
        }
        _uiState.value = AuthUiState.Loading
        viewModelScope.launch {
            if (usesFirebase) {
                if (activity == null) {
                    _uiState.value = AuthUiState.Error("Couldn't start verification. Please try again.")
                    return@launch
                }
                val normalizedPhone = normalizePhoneToE164(phone, brandingRepository.cached.value?.defaultMobileCountryCode)
                when (val result = firebasePhoneAuthService.startVerification(activity, normalizedPhone)) {
                    is PhoneAuthStartResult.CodeSent -> {
                        _uiState.value = AuthUiState.Idle
                        onResult(PhoneEntryResult.CodeSent(normalizedPhone))
                    }
                    is PhoneAuthStartResult.AutoVerified -> completeWithFirebaseToken(normalizedPhone, result.idToken, onResult)
                    is PhoneAuthStartResult.Failed -> _uiState.value = AuthUiState.Error(result.message)
                }
            } else {
                when (val result = authRepository.sendOtp(phone.trim(), flow)) {
                    is NetworkResult.Success -> {
                        _uiState.value = AuthUiState.Idle
                        onResult(PhoneEntryResult.CodeSent(phone.trim()))
                    }
                    is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
                }
            }
        }
    }

    private suspend fun completeWithFirebaseToken(phone: String, idToken: String, onResult: (PhoneEntryResult) -> Unit) {
        when (flow) {
            AuthFlow.LOGIN -> when (val result = authRepository.loginWithPhone(phone, idToken)) {
                is NetworkResult.Success -> {
                    _uiState.value = AuthUiState.Success
                    onResult(PhoneEntryResult.Completed(OtpVerifyResult.LoggedIn, phone))
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
            else -> when (val result = authRepository.verifyFirebasePhone(phone, idToken, flow)) {
                is NetworkResult.Success -> {
                    val token = result.data.verifiedPhoneToken
                    _uiState.value = AuthUiState.Success
                    if (token == null) {
                        _uiState.value = AuthUiState.Error("Unexpected response from server.")
                        return
                    }
                    val outcome = if (flow == AuthFlow.SIGNUP) {
                        OtpVerifyResult.ReadyToRegister(token)
                    } else {
                        OtpVerifyResult.ReadyToResetPassword(token)
                    }
                    onResult(PhoneEntryResult.Completed(outcome, phone))
                }
                is NetworkResult.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }
    }
}
