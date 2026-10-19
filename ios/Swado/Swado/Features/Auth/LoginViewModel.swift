import Foundation

@MainActor
final class LoginViewModel: ObservableObject {
    @Published private(set) var state: AuthUIState = .idle

    private let authRepository: AuthRepository

    init(authRepository: AuthRepository) {
        self.authRepository = authRepository
    }

    func loginWithEmail(email: String, password: String) async -> Bool {
        guard !email.isEmpty, !password.isEmpty else {
            state = .error("Enter your email and password.")
            return false
        }
        state = .loading
        do {
            _ = try await authRepository.login(email: email.trimmingCharacters(in: .whitespaces), password: password)
            state = .success
            return true
        } catch {
            state = .error(error.localizedDescription)
            return false
        }
    }

    func sendLoginOtp(phone: String) async -> Bool {
        guard !phone.isEmpty else {
            state = .error("Enter your phone number.")
            return false
        }
        state = .loading
        do {
            _ = try await authRepository.sendOtp(phone: phone.trimmingCharacters(in: .whitespaces), flow: AuthFlow.login)
            state = .idle
            return true
        } catch {
            state = .error(error.localizedDescription)
            return false
        }
    }

    func clearError() {
        if case .error = state { state = .idle }
    }
}
