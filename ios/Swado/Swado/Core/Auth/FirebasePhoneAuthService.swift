import Foundation
import FirebaseAuth

enum PhoneAuthStartResult {
    case codeSent(verificationId: String)
    case failed(String)
}

/// Wraps Firebase Phone Auth (used when GET /app/branding returns
/// otp_service_provider="firebase" — see docs/api-contract/auth.md). Holds
/// the in-flight verificationId between the phone-entry and code-verify
/// screens since it's a single linear flow.
final class FirebasePhoneAuthService {
    private var verificationId: String?

    func startVerification(phoneNumber: String) async -> PhoneAuthStartResult {
        await withCheckedContinuation { continuation in
            PhoneAuthProvider.provider().verifyPhoneNumber(phoneNumber, uiDelegate: nil) { id, error in
                if let error {
                    continuation.resume(returning: .failed(error.localizedDescription))
                    return
                }
                guard let id else {
                    continuation.resume(returning: .failed("Couldn't start phone verification."))
                    return
                }
                self.verificationId = id
                continuation.resume(returning: .codeSent(verificationId: id))
            }
        }
    }

    /// Returns the Firebase ID token to hand to the backend's
    /// phone-verify/login-with-phone endpoints.
    func confirmCode(_ code: String) async throws -> String {
        guard let verificationId else {
            throw AuthError.message("No verification in progress — send a code first.")
        }
        let credential = PhoneAuthProvider.provider().credential(withVerificationID: verificationId, verificationCode: code)
        let result = try await Auth.auth().signIn(with: credential)
        let token = try await result.user.getIDToken()
        // The token has round-tripped to our backend already once returned;
        // no need to keep a parallel Firebase Auth session alongside our own.
        try? Auth.auth().signOut()
        self.verificationId = nil
        return token
    }
}
