package com.renjo.app.data.network.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * Shapes per d:/karn/Swado/docs/api-contract/auth.md, transcribed from real
 * calls against the running backend (not just PHP source).
 */

@Serializable
data class AuthEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: AuthData? = null,
)

@Serializable
data class AuthData(
    val user: UserDto,
    val token: String? = null,
    // present on loginWithPhone / social-login responses, harmless elsewhere
    val phone: String? = null,
    val provider: String? = null,
    val firebaseUid: String? = null,
)

@Serializable
data class UserDto(
    val id: Int,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    // Deliberately no `roles` field: the real payload's `roles` is an array
    // of full Spatie role OBJECTS ({id,name,guard_name,pivot,permissions,...}),
    // not strings — declaring it as List<String> previously crashed JSON
    // decoding right at this field (kotlinx.serialization "unexpected JSON
    // token" at the byte offset where the first role object starts). Nothing
    // in the app needs role details beyond this singular `role` string.
    val role: String? = null,
    val profileImage: String? = null,
    val profilePhotoUrl: String? = null,
    val referralCode: String? = null,
    val rewardPointsBalance: Int? = null,
    val settings: UserSettingsDto? = null,
)

@Serializable
data class UserSettingsDto(
    val currencyCode: String? = null,
    val currencySymbol: String? = null,
    val enabledPaymentGateways: List<String> = emptyList(),
    val codEnabled: Boolean = true,
    val otpServiceProvider: String? = null,
    val defaultMobileCountryCode: String? = null,
)

@Serializable
data class UserEnvelope(
    val success: Boolean,
    val data: UserDto,
)

// --- Phone status ---

@Serializable
data class PhoneStatusEnvelope(val success: Boolean, val data: PhoneStatusDto)

@Serializable
data class PhoneStatusDto(
    val phone: String,
    val exists: Boolean,
    val matchesRole: Boolean? = null,
    val defaultMobileCountryCode: String? = null,
)

// --- Custom OTP flow ---

@Serializable
data class SendOtpRequest(
    val phone: String,
    val flow: String, // login|signup|forgot_password
    val role: String? = null,
)

@Serializable
data class SendOtpEnvelope(val success: Boolean, val message: String? = null, val data: SendOtpDataDto? = null)

@Serializable
data class SendOtpDataDto(
    val phone: String,
    val flow: String,
    @SerialName("existing_user") val existingUser: Boolean? = null,
    val provider: String? = null,
    val expiresIn: Int? = null,
)

@Serializable
data class VerifyOtpRequest(
    val phone: String,
    val otp: String,
    val flow: String,
    val role: String? = null,
)

/**
 * flow=login responses include user+token (reuse AuthEnvelope shape);
 * flow=signup/forgot_password responses have no user/token, just a
 * verified_phone_token — represented separately since AuthEnvelope.data.user
 * is non-nullable and wouldn't parse.
 */
@Serializable
data class VerifyOtpEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: VerifyOtpDataDto? = null,
)

@Serializable
data class VerifyOtpDataDto(
    val user: UserDto? = null,
    val token: String? = null,
    val phone: String? = null,
    val verifiedPhoneToken: String? = null,
    val requiresRegistration: Boolean? = null,
    val requiresPasswordReset: Boolean? = null,
)

// --- Email/password ---

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    val role: String? = null,
)

// --- Registration (requires verifiedPhoneToken from OTP/Firebase verify) ---

@Serializable
data class RegisterRequest(
    val name: String,
    val email: String? = null,
    val phone: String,
    @SerialName("verified_phone_token") val verifiedPhoneToken: String,
    val role: String? = null,
    @SerialName("referral_code") val referralCode: String? = null,
)

// --- Forgot password ---

@Serializable
data class ResetPasswordByPhoneRequest(
    val phone: String,
    @SerialName("verified_phone_token") val verifiedPhoneToken: String,
    val password: String,
    @SerialName("password_confirmation") val passwordConfirmation: String,
    val role: String? = null,
)

@Serializable
data class SimpleEnvelope(val success: Boolean, val message: String? = null)

// --- Social / Firebase phone (structurally complete; needs real Firebase
// project credentials from the user before it can be exercised — see
// docs/api-contract/auth.md "known quirks") ---

@Serializable
data class SocialLoginRequest(
    val provider: String, // google|apple
    @SerialName("firebase_id_token") val firebaseIdToken: String,
    val role: String? = null,
    @SerialName("display_name") val displayName: String? = null,
    @SerialName("referral_code") val referralCode: String? = null,
)

@Serializable
data class LoginWithPhoneRequest(
    val phone: String,
    @SerialName("firebase_id_token") val firebaseIdToken: String,
    val role: String? = null,
)

@Serializable
data class VerifyFirebasePhoneRequest(
    val phone: String,
    @SerialName("firebase_id_token") val firebaseIdToken: String,
    val flow: String, // signup|forgot_password
    val role: String? = null,
)
