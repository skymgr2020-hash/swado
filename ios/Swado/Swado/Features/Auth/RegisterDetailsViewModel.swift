import Foundation

@MainActor
final class RegisterDetailsViewModel: ObservableObject {
    @Published private(set) var state: AuthUIState = .idle
    let phone: String
    let verifiedPhoneToken: String

    private let authRepository: AuthRepository

    init(phone: String, verifiedPhoneToken: String, authRepository: AuthRepository) {
        self.phone = phone
        self.verifiedPhoneToken = verifiedPhoneToken
        self.authRepository = authRepository
    }

    func register(name: String, email: String) async -> Bool {
        guard !name.isEmpty else {
            state = .error("Enter your name.")
            return false
        }
        state = .loading
        do {
            _ = try await authRepository.register(
                name: name.trimmingCharacters(in: .whitespaces),
                email: email.trimmingCharacters(in: .whitespaces).isEmpty ? nil : email,
                phone: phone,
                verifiedPhoneToken: verifiedPhoneToken,
                referralCode: nil
            )
            state = .success
            return true
        } catch {
            state = .error(error.localizedDescription)
            return false
        }
    }
}
