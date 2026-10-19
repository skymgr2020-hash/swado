import Foundation

@MainActor
final class AuthRepository {
    private let apiClient: APIClient
    private let sessionManager: SessionManager

    init(apiClient: APIClient, sessionManager: SessionManager) {
        self.apiClient = apiClient
        self.sessionManager = sessionManager
    }

    func sendOtp(phone: String, flow: String, role: String? = "customer") async throws -> String? {
        let response = try await apiClient.post(
            "auth/otp/send",
            body: SendOtpRequest(phone: phone, flow: flow, role: role),
            as: SendOtpEnvelope.self
        )
        guard response.success else { throw AuthError.message(response.message ?? "Couldn't send OTP.") }
        return response.message
    }

    func verifyOtp(phone: String, otp: String, flow: String, role: String? = "customer") async throws -> VerifyOtpDataDTO {
        let response = try await apiClient.post(
            "auth/otp/verify",
            body: VerifyOtpRequest(phone: phone, otp: otp, flow: flow, role: role),
            as: VerifyOtpEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "OTP verification failed.") }
        if flow == "login", let token = data.token, let user = data.user {
            sessionManager.startSession(token: token, user: user)
        }
        return data
    }

    func login(email: String, password: String) async throws -> UserDTO {
        let response = try await apiClient.post(
            "login",
            body: LoginRequest(email: email, password: password, role: "customer"),
            as: AuthEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "Login failed.") }
        guard let token = data.token else { throw AuthError.message("Server did not return a session token.") }
        sessionManager.startSession(token: token, user: data.user)
        return data.user
    }

    func register(name: String, email: String?, phone: String, verifiedPhoneToken: String, referralCode: String?) async throws -> UserDTO {
        let response = try await apiClient.post(
            "register",
            body: RegisterRequest(
                name: name, email: email, phone: phone, verifiedPhoneToken: verifiedPhoneToken,
                role: "customer", referralCode: referralCode
            ),
            as: AuthEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "Registration failed.") }
        guard let token = data.token else { throw AuthError.message("Server did not return a session token.") }
        sessionManager.startSession(token: token, user: data.user)
        return data.user
    }

    func resetPasswordByPhone(phone: String, verifiedPhoneToken: String, password: String) async throws {
        let response = try await apiClient.post(
            "forgot-password/reset-by-phone",
            body: ResetPasswordByPhoneRequest(
                phone: phone, verifiedPhoneToken: verifiedPhoneToken,
                password: password, passwordConfirmation: password, role: "customer"
            ),
            as: SimpleEnvelope.self
        )
        guard response.success else { throw AuthError.message(response.message ?? "Couldn't reset password.") }
    }

    func logout() async {
        defer { sessionManager.clearSession() }
        _ = try? await apiClient.post("logout", as: SimpleEnvelope.self)
    }

    @discardableResult
    func refreshCurrentUser() async throws -> UserDTO {
        let user = try await apiClient.get("user", as: UserEnvelope.self).data
        sessionManager.updateUser(user)
        return user
    }

    // Firebase-dependent — see docs/api-contract/auth.md "known quirks".

    func socialLogin(provider: String, firebaseIdToken: String, displayName: String?) async throws -> UserDTO {
        let response = try await apiClient.post(
            "auth/social-login",
            body: SocialLoginRequest(provider: provider, firebaseIdToken: firebaseIdToken, role: "customer", displayName: displayName, referralCode: nil),
            as: AuthEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "Social login failed.") }
        guard let token = data.token else { throw AuthError.message("Server did not return a session token.") }
        sessionManager.startSession(token: token, user: data.user)
        return data.user
    }

    func loginWithPhone(phone: String, firebaseIdToken: String) async throws -> UserDTO {
        let response = try await apiClient.post(
            "auth/login-with-phone",
            body: LoginWithPhoneRequest(phone: phone, firebaseIdToken: firebaseIdToken, role: "customer"),
            as: AuthEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "Login failed.") }
        guard let token = data.token else { throw AuthError.message("Server did not return a session token.") }
        sessionManager.startSession(token: token, user: data.user)
        return data.user
    }

    func verifyFirebasePhone(phone: String, firebaseIdToken: String, flow: String) async throws -> VerifyOtpDataDTO {
        let response = try await apiClient.post(
            "auth/phone/verify-firebase",
            body: VerifyFirebasePhoneRequest(phone: phone, firebaseIdToken: firebaseIdToken, flow: flow, role: "customer"),
            as: VerifyOtpEnvelope.self
        )
        guard let data = response.data else { throw AuthError.message(response.message ?? "Phone verification failed.") }
        return data
    }
}

enum AuthError: Error, LocalizedError {
    case message(String)
    var errorDescription: String? {
        switch self { case .message(let text): return text }
    }
}
