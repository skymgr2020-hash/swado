import Foundation

@MainActor
final class OnboardingViewModel: ObservableObject {
    @Published private(set) var branding: Branding?

    private let appPreferences: AppPreferences
    private let brandingRepository: BrandingRepository

    init(appPreferences: AppPreferences, brandingRepository: BrandingRepository) {
        self.appPreferences = appPreferences
        self.brandingRepository = brandingRepository
        Task { await loadBranding() }
    }

    private func loadBranding() async {
        // Splash already fetched this once; reuse the cache instead of a
        // redundant network call, matching Android's BrandingRepository.cached.
        if let cached = brandingRepository.cached {
            branding = cached
            return
        }
        branding = try? await brandingRepository.getBranding()
    }

    func markSeen() {
        appPreferences.hasSeenOnboarding = true
    }
}
