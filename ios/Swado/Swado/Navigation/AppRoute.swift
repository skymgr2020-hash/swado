import Foundation

/// Auth-flow-scoped push destinations (used inside AuthFlowView's own
/// NavigationStack path). The top-level root switch — Splash / Onboarding /
/// AuthFlow / Home — is separate app state in SwadoRootView, not part of this
/// enum, since those are whole-screen swaps rather than pushes.
enum AppRoute: Hashable {
    case phoneEntry(flow: String)
    case otpVerify(phone: String, flow: String)
    case registerDetails(phone: String, token: String)
    case resetPassword(phone: String, token: String)
}

/// Mirrors Android's AuthFlow object — flow identifiers shared with the
/// backend's `flow` request param.
enum AuthFlow {
    static let login = "login"
    static let signup = "signup"
    static let forgotPassword = "forgot_password"
}
