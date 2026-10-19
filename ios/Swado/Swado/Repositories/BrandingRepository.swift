import Foundation

@MainActor
final class BrandingRepository {
    private let apiClient: APIClient

    /// Last successfully fetched branding — Splash fetches it first, so by
    /// the time auth screens need it (e.g. to pick the phone-verification
    /// path) it's already warm.
    private(set) var cached: Branding?

    init(apiClient: APIClient) {
        self.apiClient = apiClient
    }

    @discardableResult
    func getBranding() async throws -> Branding {
        let branding = try await apiClient.get("app/branding", as: BrandingEnvelope.self).data
        cached = branding
        return branding
    }
}
