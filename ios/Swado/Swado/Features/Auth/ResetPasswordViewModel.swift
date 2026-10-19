import Foundation

@MainActor
final class ResetPasswordViewModel: ObservableObject {
    @Published private(set) var state: AuthUIState = .idle
    let phone: String
    let verifiedPhoneToken: String

    private let authRepository: AuthRepository

    init(phone: String, verifiedPhoneToken: String, authRepository: AuthRepository) {
        self.phone = phone
        self.verifiedPhoneToken = verifiedPhoneToken
        self.authRepository = authRepository
    }

    func resetPassword(password: String, confirmPassword: String) async -> Bool {
        guard password.count >= 6 else {
            state = .error("Password must be at least 6 characters.")
            return false
        }
        guard password == confirmPassword else {
            state = .error("Passwords don't match.")
            return false
        }
        state = .loading
        do {
            try await authRepository.resetPasswordByPhone(phone: phone, verifiedPhoneToken: verifiedPhoneToken, password: password)
            state = .success
            return true
        } catch {
            state = .error(error.localizedDescription)
            return false
        }
    }
}
