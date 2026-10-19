import Foundation

enum OtpVerifyResult {
    case loggedIn
    case readyToRegister(verifiedPhoneToken: String)
    case readyToResetPassword(verifiedPhoneToken: String)
}

@MainActor
final class OtpVerifyViewModel: ObservableObject {
    @Published private(set) var state: AuthUIState = .idle
    let phone: String
    let flow: String

    private let authRepository: AuthRepository
    private let brandingRepository: BrandingRepository
    private let firebasePhoneAuthService: FirebasePhoneAuthService

    init(phone: String, flow: String, authRepository: AuthRepository, brandingRepository: BrandingRepository, firebasePhoneAuthService: FirebasePhoneAuthService) {
        self.phone = phone
        self.flow = flow
        self.authRepository = authRepository
        self.brandingRepository = brandingRepository
        self.firebasePhoneAuthService = firebasePhoneAuthService
    }

    private var usesFirebase: Bool {
        brandingRepository.cached?.otpServiceProvider == "firebase"
    }

    func verify(code: String) async -> OtpVerifyResult? {
        guard !code.isEmpty else {
            state = .error("Enter the code we sent you.")
            return nil
        }
        state = .loading
        return usesFirebase ? await verifyWithFirebase(code: code) : await verifyWithBackendOtp(code: code)
    }

    private func verifyWithBackendOtp(code: String) async -> OtpVerifyResult? {
        do {
            let data = try await authRepository.verifyOtp(phone: phone, otp: code.trimmingCharacters(in: .whitespaces), flow: flow)
            state = .success
            if flow == AuthFlow.login, data.token != nil {
                return .loggedIn
            } else if flow == AuthFlow.signup, let token = data.verifiedPhoneToken {
                return .readyToRegister(verifiedPhoneToken: token)
            } else if flow == AuthFlow.forgotPassword, let token = data.verifiedPhoneToken {
                return .readyToResetPassword(verifiedPhoneToken: token)
            } else {
                state = .error("Unexpected response from server.")
                return nil
            }
        } catch {
            state = .error(error.localizedDescription)
            return nil
        }
    }

    private func verifyWithFirebase(code: String) async -> OtpVerifyResult? {
        let idToken: String
        do {
            idToken = try await firebasePhoneAuthService.confirmCode(code.trimmingCharacters(in: .whitespaces))
        } catch {
            state = .error(error.localizedDescription)
            return nil
        }

        do {
            if flow == AuthFlow.login {
                _ = try await authRepository.loginWithPhone(phone: phone, firebaseIdToken: idToken)
                state = .success
                return .loggedIn
            } else {
                let data = try await authRepository.verifyFirebasePhone(phone: phone, firebaseIdToken: idToken, flow: flow)
                guard let token = data.verifiedPhoneToken else {
                    state = .error("Unexpected response from server.")
                    return nil
                }
                state = .success
                return flow == AuthFlow.signup
                    ? .readyToRegister(verifiedPhoneToken: token)
                    : .readyToResetPassword(verifiedPhoneToken: token)
            }
        } catch {
            state = .error(error.localizedDescription)
            return nil
        }
    }

    func resend() async {
        // Firebase resend re-triggers verifyPhoneNumber from this same
        // screen (no Activity-style dependency like Android needs).
        state = .loading
        if usesFirebase {
            switch await firebasePhoneAuthService.startVerification(phoneNumber: phone) {
            case .codeSent: state = .idle
            case .failed(let message): state = .error(message)
            }
        } else {
            do {
                _ = try await authRepository.sendOtp(phone: phone, flow: flow)
                state = .idle
            } catch {
                state = .error(error.localizedDescription)
            }
        }
    }
}
