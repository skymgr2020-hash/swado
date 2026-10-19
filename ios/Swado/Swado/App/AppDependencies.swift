import Foundation

/// Manual DI container — the dependency graph is small enough that a
/// third-party DI framework would add ceremony without payoff (see plan).
/// Grows one repository per phase.
@MainActor
final class AppDependencies {
    let apiClient: APIClient
    let sessionManager: SessionManager
    let appPreferences: AppPreferences
    let brandingRepository: BrandingRepository
    let authRepository: AuthRepository
    let firebasePhoneAuthService: FirebasePhoneAuthService

    init() {
        apiClient = APIClient()
        sessionManager = SessionManager()
        appPreferences = AppPreferences()
        brandingRepository = BrandingRepository(apiClient: apiClient)
        authRepository = AuthRepository(apiClient: apiClient, sessionManager: sessionManager)
        firebasePhoneAuthService = FirebasePhoneAuthService()
    }
}
