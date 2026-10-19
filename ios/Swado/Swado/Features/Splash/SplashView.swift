import SwiftUI

/// Pixel-matched to the Flutter app's _SplashView (lib/screens/app_splash_screen.dart):
/// solid #2563EB background (branding fallback - matched
/// exactly, not dynamically), splash.png wordmark at 230x110pt, white 82%-alpha
/// bold 14pt tagline, 26pt white spinner, minimum 5s display in the ViewModel.
private let splashBackground = Color(hex: "#2563EB")

struct SplashView: View {
    @StateObject var viewModel: SplashViewModel
    var onBrandingReady: (Branding) -> Void = { _ in }

    var body: some View {
        ZStack {
            splashBackground.ignoresSafeArea()
            switch viewModel.state {
            case .loading:
                splashVisual
            case .loaded(let branding):
                splashVisual
                    .onAppear { onBrandingReady(branding) }
            case .error(let message):
                errorContent(message)
            }
        }
    }

    private var splashVisual: some View {
        VStack(spacing: 0) {
            Image("SplashLogo")
                .resizable()
                .aspectRatio(contentMode: .fit)
                .frame(width: 230, height: 110)
            Spacer().frame(height: 18)
            Text("Fresh food, fast delivery")
                .font(.system(size: 14, weight: .bold))
                .foregroundStyle(Color.white.opacity(0.82))
            Spacer().frame(height: 34)
            ProgressView()
                .progressViewStyle(CircularProgressViewStyle(tint: .white))
                .scaleEffect(0.9)
                .frame(width: 26, height: 26)
        }
    }

    @ViewBuilder
    private func errorContent(_ message: String) -> some View {
        VStack(spacing: 12) {
            Text("Couldn't reach the server")
                .font(.title3.bold())
                .foregroundStyle(.white)
            Text(message)
                .font(.footnote)
                .foregroundStyle(Color.white.opacity(0.82))
                .multilineTextAlignment(.center)
            Button("Retry") {
                Task { await viewModel.load() }
            }
            .tint(.white)
        }
        .padding(24)
    }
}
