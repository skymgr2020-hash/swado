import SwiftUI

struct OnboardingView: View {
    @StateObject var viewModel: OnboardingViewModel
    var onFinished: () -> Void

    var body: some View {
        VStack(spacing: 0) {
            if let slides = viewModel.branding?.onboardingSlides, !slides.isEmpty {
                TabView {
                    ForEach(slides.indices, id: \.self) { index in
                        slideView(slides[index])
                    }
                }
                .tabViewStyle(.page)
                .frame(maxHeight: .infinity)
            } else {
                VStack(spacing: 12) {
                    Text(viewModel.branding?.onboardingIntroTitle ?? "Welcome")
                        .font(.largeTitle.bold())
                    if let subtitle = viewModel.branding?.onboardingIntroSubtitle {
                        Text(subtitle).foregroundStyle(.secondary)
                    }
                }
                .padding(24)
                .frame(maxHeight: .infinity)
            }

            Button {
                viewModel.markSeen()
                onFinished()
            } label: {
                Text("Get started").frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .padding(24)
        }
    }

    @ViewBuilder
    private func slideView(_ slide: OnboardingSlide) -> some View {
        VStack(spacing: 16) {
            AsyncImage(url: URL(string: slide.image)) { image in
                image.resizable().aspectRatio(contentMode: .fit)
            } placeholder: {
                Color.clear
            }
            .frame(maxHeight: .infinity)

            Text(slide.title)
                .font(.title2.bold())
                .multilineTextAlignment(.center)
            Text(slide.description)
                .foregroundStyle(.secondary)
                .multilineTextAlignment(.center)
        }
        .padding(24)
    }
}
