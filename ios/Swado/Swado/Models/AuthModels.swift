import Foundation

/// Shapes per d:/karn/Swado/docs/api-contract/auth.md, transcribed from real
/// calls against the running backend (not just PHP source). Mirrors
/// android/.../data/network/dto/AuthDto.kt field-for-field.

struct AuthEnvelope: Decodable {
    let success: Bool
    let message: String?
    let data: AuthData?
}

struct AuthData: Decodable {
    let user: UserDTO
    let token: String?
    let phone: String?
    let provider: String?
    let firebaseUid: String?
}

struct UserDTO: Decodable {
    let id: Int
    let name: String
    let email: String?
    let phone: String?
    // Deliberately no `roles` property: the real payload's `roles` is an
    // array of full Spatie role OBJECTS ({id,name,guard_name,pivot,
    // permissions,...}), not strings — declaring [String] here would crash
    // decoding the moment a role object is encountered. Nothing in the app
    // needs role details beyond this singular `role` string; JSONDecoder
    // ignores undeclared keys, so simply omitting the property is enough.
    let role: String?
    let profileImage: String?
    let profilePhotoUrl: String?
    let referralCode: String?
    let rewardPointsBalance: Int?
    let settings: UserSettingsDTO?
}

struct UserSettingsDTO: Decodable {
    let currencyCode: String?
    let currencySymbol: String?
    let enabledPaymentGateways: [String]?
    let codEnabled: Bool?
    let otpServiceProvider: String?
    let defaultMobileCountryCode: String?
}

struct UserEnvelope: Decodable {
    let success: Bool
    let data: UserDTO
}

// MARK: Phone status

struct PhoneStatusEnvelope: Decodable {
    let success: Bool
    let data: PhoneStatusDTO
}

struct PhoneStatusDTO: Decodable {
    let phone: String
    let exists: Bool
    let matchesRole: Bool?
    let defaultMobileCountryCode: String?
}

// MARK: Custom OTP flow

struct SendOtpRequest: Encodable {
    let phone: String
    let flow: String
    let role: String?
}

struct SendOtpEnvelope: Decodable {
    let success: Bool
    let message: String?
    let data: SendOtpDataDTO?
}

struct SendOtpDataDTO: Decodable {
    let phone: String
    let flow: String
    let existingUser: Bool?
    let provider: String?
    let expiresIn: Int?
}

struct VerifyOtpRequest: Encodable {
    let phone: String
    let otp: String
    let flow: String
    let role: String?
}

/// flow=login responses include user+token; flow=signup/forgot_password
/// responses only carry verifiedPhoneToken — all optional here since one
/// decodable type covers every flow's response shape.
struct VerifyOtpEnvelope: Decodable {
    let success: Bool
    let message: String?
    let data: VerifyOtpDataDTO?
}

struct VerifyOtpDataDTO: Decodable {
    let user: UserDTO?
    let token: String?
    let phone: String?
    let verifiedPhoneToken: String?
    let requiresRegistration: Bool?
    let requiresPasswordReset: Bool?
}

// MARK: Email/password

struct LoginRequest: Encodable {
    let email: String
    let password: String
    let role: String?
}

// MARK: Registration

struct RegisterRequest: Encodable {
    let name: String
    let email: String?
    let phone: String
    let verifiedPhoneToken: String
    let role: String?
    let referralCode: String?

    enum CodingKeys: String, CodingKey {
        case name, email, phone, role
        case verifiedPhoneToken = "verified_phone_token"
        case referralCode = "referral_code"
    }
}

// MARK: Forgot password

struct ResetPasswordByPhoneRequest: Encodable {
    let phone: String
    let verifiedPhoneToken: String
    let password: String
    let passwordConfirmation: String
    let role: String?

    enum CodingKeys: String, CodingKey {
        case phone, password, role
        case verifiedPhoneToken = "verified_phone_token"
        case passwordConfirmation = "password_confirmation"
    }
}

struct SimpleEnvelope: Decodable {
    let success: Bool
    let message: String?
}

// MARK: Social / Firebase phone (structurally complete; needs the user's real
// Firebase project credentials before it can be exercised — see
// docs/api-contract/auth.md "known quirks")

struct SocialLoginRequest: Encodable {
    let provider: String
    let firebaseIdToken: String
    let role: String?
    let displayName: String?
    let referralCode: String?

    enum CodingKeys: String, CodingKey {
        case provider, role
        case firebaseIdToken = "firebase_id_token"
        case displayName = "display_name"
        case referralCode = "referral_code"
    }
}

struct LoginWithPhoneRequest: Encodable {
    let phone: String
    let firebaseIdToken: String
    let role: String?

    enum CodingKeys: String, CodingKey {
        case phone, role
        case firebaseIdToken = "firebase_id_token"
    }
}

struct VerifyFirebasePhoneRequest: Encodable {
    let phone: String
    let firebaseIdToken: String
    let flow: String
    let role: String?

    enum CodingKeys: String, CodingKey {
        case phone, flow, role
        case firebaseIdToken = "firebase_id_token"
    }
}
