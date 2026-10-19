import Foundation

/// Minimum splash display time, matching the Flutter app's
/// Future.wait([startupFuture, Future.delayed(5s)]) in app_splash_screen.dart.
private let minimumSplashDurationNs: UInt64 = 5_000_000_000

@MainActor
final class SplashViewModel: ObservableObject {
    enum State {
        case loading
        case loaded(Branding)
        case error(String)
    }

    @Published private(set) var state: State = .loading

    private let brandingRepository: BrandingRepository

    init(brandingRepository: BrandingRepository) {
        self.brandingRepository = brandingRepository
        Task { await load() }
    }

    func load() async {
        state = .loading
        async let brandingResult: Result<Branding, Error> = {
            do { return .success(try await brandingRepository.getBranding()) }
            catch { return .failure(error) }
        }()
        async let minimumDelay: Void = try? Task.sleep(nanoseconds: minimumSplashDurationNs)
        let (result, _) = await (brandingResult, minimumDelay)
        switch result {
        case .success(let branding): state = .loaded(branding)
        case .failure(let error): state = .error(error.localizedDescription)
        }
    }
}
