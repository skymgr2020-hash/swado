import Foundation

/// Placeholder post-login screen for Phase 1. Phase 2 replaces this with the
/// real bottom-nav Home/Search/Orders/Profile shell and restaurant discovery.
@MainActor
final class HomeViewModel: ObservableObject {
    private let authRepository: AuthRepository

    init(authRepository: AuthRepository) {
        self.authRepository = authRepository
        Task { try? await authRepository.refreshCurrentUser() }
    }

    func logout() async {
        await authRepository.logout()
    }
}
