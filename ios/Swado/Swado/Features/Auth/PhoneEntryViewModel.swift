import Foundation

/// What happens after tapping "Send OTP" — either a code was sent (proceed to
/// the verify screen) or Firebase auto-verified instantly, in which case the
/// flow is already complete.
enum PhoneEntryResult {
    case codeSent(phone: String)
    case completed(outcome: OtpVerifyResult, phone: String)
}

@MainActor
final class PhoneEntryViewModel: ObservableObject {
    @Published private(set) var state: AuthUIState = .idle
    let flow: String

    private let authRepository: AuthRepository
    private let brandingRepository: BrandingRepository
    private let firebasePhoneAuthService: FirebasePhoneAuthService

    init(flow: String, authRepository: AuthRepository, brandingRepository: BrandingRepository, firebasePhoneAuthService: FirebasePhoneAuthService) {
        self.flow = flow
        self.authRepository = authRepository
        self.brandingRepository = brandingRepository
        self.firebasePhoneAuthService = firebasePhoneAuthService
    }

    private var usesFirebase: Bool {
        brandingRepository.cached?.otpServiceProvider == "firebase"
    }

    func sendOtp(phone: String) async -> PhoneEntryResult? {
        guard !phone.isEmpty else {
            state = .error("Enter your phone number.")
            return nil
        }
        state = .loading

        if usesFirebase {
            let normalizedPhone = normalizePhoneToE164(phone, defaultCountryCode: brandingRepository.cached?.defaultMobileCountryCode)
            switch await firebasePhoneAuthService.startVerification(phoneNumber: normalizedPhone) {
            case .codeSent:
                state = .idle
                return .codeSent(phone: normalizedPhone)
            case .failed(let message):
                state = .error(message)
                return nil
            }
        } else {
            let trimmedPhone = phone.trimmingCharacters(in: .whitespaces)
            do {
                _ = try await authRepository.sendOtp(phone: trimmedPhone, flow: flow)
                state = .idle
                return .codeSent(phone: trimmedPhone)
            } catch {
                state = .error(error.localizedDescription)
                return nil
            }
        }
    }
}
