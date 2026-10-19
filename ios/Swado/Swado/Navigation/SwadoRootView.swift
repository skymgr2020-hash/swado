import SwiftUI

private enum RootStage {
    case splash
    case onboarding
    case authFlow
    case home
}

/// Top-level Splash / Onboarding / AuthFlow / Home switch — these are
/// whole-screen swaps, not pushes, so they live outside AppRoute/NavigationStack.
struct SwadoRootView: View {
    let dependencies: AppDependencies
    @ObservedObject private var sessionManager: SessionManager
    @State private var stage: RootStage = .splash

    init(dependencies: AppDependencies) {
        self.dependencies = dependencies
        self.sessionManager = dependencies.sessionManager
    }

    var body: some View {
        Group {
            switch stage {
            case .splash:
                SplashView(
                    viewModel: SplashViewModel(brandingRepository: dependencies.brandingRepository),
                    onBrandingReady: { _ in advancePastSplash() }
                )
            case .onboarding:
                OnboardingView(
                    viewModel: OnboardingViewModel(appPreferences: dependencies.appPreferences, brandingRepository: dependencies.brandingRepository),
                    onFinished: { stage = .authFlow }
                )
            case .authFlow:
                AuthFlowView(dependencies: dependencies, onLoggedIn: { stage = .home })
            case .home:
                HomeView(
                    viewModel: HomeViewModel(authRepository: dependencies.authRepository),
                    sessionManager: sessionManager,
                    onLoggedOut: { stage = .authFlow }
                )
            }
        }
    }

    private func advancePastSplash() {
        stage = dependencies.resolveStartDestination()
    }
}

private extension AppDependencies {
    func resolveStartDestination() -> RootStage {
        if sessionManager.isLoggedIn { return .home }
        if !appPreferences.hasSeenOnboarding { return .onboarding }
        return .authFlow
    }
}
