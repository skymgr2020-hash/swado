import SwiftUI

/// Hosts the linear auth push-stack — Login is the root, everything else
/// (phone entry, OTP verify, register details, reset password) is pushed
/// onto `path`. Mirrors Android's single NavHost auth routes.
struct AuthFlowView: View {
    let dependencies: AppDependencies
    var onLoggedIn: () -> Void

    @State private var path: [AppRoute] = []

    var body: some View {
        NavigationStack(path: $path) {
            LoginView(
                viewModel: LoginViewModel(authRepository: dependencies.authRepository),
                onLoggedIn: onLoggedIn,
                onContinueWithPhone: { path.append(.phoneEntry(flow: AuthFlow.login)) },
                onNavigateToSignup: { path.append(.phoneEntry(flow: AuthFlow.signup)) },
                onNavigateToForgotPassword: { path.append(.phoneEntry(flow: AuthFlow.forgotPassword)) }
            )
            .navigationDestination(for: AppRoute.self) { route in
                destination(for: route)
            }
        }
    }

    @ViewBuilder
    private func destination(for route: AppRoute) -> some View {
        switch route {
        case .phoneEntry(let flow):
            PhoneEntryView(
                viewModel: PhoneEntryViewModel(
                    flow: flow,
                    authRepository: dependencies.authRepository,
                    brandingRepository: dependencies.brandingRepository,
                    firebasePhoneAuthService: dependencies.firebasePhoneAuthService
                ),
                onOtpSent: { phone in path.append(.otpVerify(phone: phone, flow: flow)) },
                onCompleted: { outcome, phone in handleAuthOutcome(outcome, phone: phone) }
            )
        case .otpVerify(let phone, let flow):
            OtpVerifyView(
                viewModel: OtpVerifyViewModel(
                    phone: phone,
                    flow: flow,
                    authRepository: dependencies.authRepository,
                    brandingRepository: dependencies.brandingRepository,
                    firebasePhoneAuthService: dependencies.firebasePhoneAuthService
                ),
                onResult: { result in handleAuthOutcome(result, phone: phone) }
            )
        case .registerDetails(let phone, let token):
            RegisterDetailsView(
                viewModel: RegisterDetailsViewModel(phone: phone, verifiedPhoneToken: token, authRepository: dependencies.authRepository),
                onRegistered: onLoggedIn
            )
        case .resetPassword(let phone, let token):
            ResetPasswordView(
                viewModel: ResetPasswordViewModel(phone: phone, verifiedPhoneToken: token, authRepository: dependencies.authRepository),
                onDone: { path.removeAll() }
            )
        }
    }

    private func handleAuthOutcome(_ result: OtpVerifyResult, phone: String) {
        switch result {
        case .loggedIn: onLoggedIn()
        case .readyToRegister(let token): path.append(.registerDetails(phone: phone, token: token))
        case .readyToResetPassword(let token): path.append(.resetPassword(phone: phone, token: token))
        }
    }
}
