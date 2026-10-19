package com.renjo.app.data.repository

import com.renjo.app.data.network.ApiService
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.LoginRequest
import com.renjo.app.data.network.dto.LoginWithPhoneRequest
import com.renjo.app.data.network.dto.PhoneStatusDto
import com.renjo.app.data.network.dto.RegisterRequest
import com.renjo.app.data.network.dto.ResetPasswordByPhoneRequest
import com.renjo.app.data.network.dto.SendOtpRequest
import com.renjo.app.data.network.dto.SocialLoginRequest
import com.renjo.app.data.network.dto.UserDto
import com.renjo.app.data.network.dto.VerifyFirebasePhoneRequest
import com.renjo.app.data.network.dto.VerifyOtpDataDto
import com.renjo.app.data.network.dto.VerifyOtpRequest
import com.renjo.app.data.network.safeApiCall
import com.renjo.app.data.session.SessionManager
import javax.inject.Inject

interface AuthRepository {
    suspend fun phoneStatus(phone: String, role: String? = "customer"): NetworkResult<PhoneStatusDto>
    suspend fun sendOtp(phone: String, flow: String, role: String? = "customer"): NetworkResult<String?>
    suspend fun verifyOtp(phone: String, otp: String, flow: String, role: String? = "customer"): NetworkResult<VerifyOtpDataDto>
    suspend fun login(email: String, password: String): NetworkResult<UserDto>
    suspend fun register(name: String, email: String?, phone: String, verifiedPhoneToken: String, referralCode: String?): NetworkResult<UserDto>
    suspend fun resetPasswordByPhone(phone: String, verifiedPhoneToken: String, password: String): NetworkResult<Unit>
    suspend fun logout(): NetworkResult<Unit>
    suspend fun refreshCurrentUser(): NetworkResult<UserDto>

    // Firebase-dependent — see docs/api-contract/auth.md "known quirks".
    suspend fun socialLogin(provider: String, firebaseIdToken: String, displayName: String?): NetworkResult<UserDto>
    suspend fun loginWithPhone(phone: String, firebaseIdToken: String): NetworkResult<UserDto>
    suspend fun verifyFirebasePhone(phone: String, firebaseIdToken: String, flow: String): NetworkResult<VerifyOtpDataDto>
}

class AuthRepositoryImpl @Inject constructor(
    private val apiService: ApiService,
    private val sessionManager: SessionManager,
) : AuthRepository {

    override suspend fun phoneStatus(phone: String, role: String?): NetworkResult<PhoneStatusDto> = safeApiCall {
        apiService.phoneStatus(phone, role).data
    }

    override suspend fun sendOtp(phone: String, flow: String, role: String?): NetworkResult<String?> = safeApiCall {
        val response = apiService.sendOtp(SendOtpRequest(phone = phone, flow = flow, role = role))
        if (!response.success) error(response.message ?: "Couldn't send OTP.")
        response.message
    }

    override suspend fun verifyOtp(phone: String, otp: String, flow: String, role: String?): NetworkResult<VerifyOtpDataDto> =
        safeApiCall {
            val response = apiService.verifyOtp(VerifyOtpRequest(phone = phone, otp = otp, flow = flow, role = role))
            val data = response.data ?: error(response.message ?: "OTP verification failed.")
            if (flow == "login" && data.token != null && data.user != null) {
                sessionManager.startSession(data.token, data.user)
            }
            data
        }

    override suspend fun login(email: String, password: String): NetworkResult<UserDto> = safeApiCall {
        val response = apiService.login(LoginRequest(email = email, password = password, role = "customer"))
        val data = response.data ?: error(response.message ?: "Login failed.")
        val token = data.token ?: error("Server did not return a session token.")
        sessionManager.startSession(token, data.user)
        data.user
    }

    override suspend fun register(
        name: String,
        email: String?,
        phone: String,
        verifiedPhoneToken: String,
        referralCode: String?,
    ): NetworkResult<UserDto> = safeApiCall {
        val response = apiService.register(
            RegisterRequest(
                name = name,
                email = email,
                phone = phone,
                verifiedPhoneToken = verifiedPhoneToken,
                role = "customer",
                referralCode = referralCode,
            ),
        )
        val data = response.data ?: error(response.message ?: "Registration failed.")
        val token = data.token ?: error("Server did not return a session token.")
        sessionManager.startSession(token, data.user)
        data.user
    }

    override suspend fun resetPasswordByPhone(phone: String, verifiedPhoneToken: String, password: String): NetworkResult<Unit> =
        safeApiCall {
            val response = apiService.resetPasswordByPhone(
                ResetPasswordByPhoneRequest(
                    phone = phone,
                    verifiedPhoneToken = verifiedPhoneToken,
                    password = password,
                    passwordConfirmation = password,
                    role = "customer",
                ),
            )
            if (!response.success) error(response.message ?: "Couldn't reset password.")
        }

    override suspend fun logout(): NetworkResult<Unit> = safeApiCall {
        try {
            apiService.logout()
        } finally {
            // Clear locally regardless of network outcome — an unreachable
            // backend shouldn't strand the user in a logged-in UI state.
            sessionManager.clearSession()
        }
    }

    override suspend fun refreshCurrentUser(): NetworkResult<UserDto> = safeApiCall {
        val user = apiService.getCurrentUser().data
        sessionManager.updateUser(user)
        user
    }

    override suspend fun socialLogin(provider: String, firebaseIdToken: String, displayName: String?): NetworkResult<UserDto> =
        safeApiCall {
            val response = apiService.socialLogin(
                SocialLoginRequest(provider = provider, firebaseIdToken = firebaseIdToken, role = "customer", displayName = displayName),
            )
            val data = response.data ?: error(response.message ?: "Social login failed.")
            val token = data.token ?: error("Server did not return a session token.")
            sessionManager.startSession(token, data.user)
            data.user
        }

    override suspend fun loginWithPhone(phone: String, firebaseIdToken: String): NetworkResult<UserDto> = safeApiCall {
        val response = apiService.loginWithPhone(LoginWithPhoneRequest(phone = phone, firebaseIdToken = firebaseIdToken, role = "customer"))
        val data = response.data ?: error(response.message ?: "Login failed.")
        val token = data.token ?: error("Server did not return a session token.")
        sessionManager.startSession(token, data.user)
        data.user
    }

    override suspend fun verifyFirebasePhone(phone: String, firebaseIdToken: String, flow: String): NetworkResult<VerifyOtpDataDto> =
        safeApiCall {
            val response = apiService.verifyFirebasePhone(
                VerifyFirebasePhoneRequest(phone = phone, firebaseIdToken = firebaseIdToken, flow = flow, role = "customer"),
            )
            response.data ?: error(response.message ?: "Phone verification failed.")
        }
}
