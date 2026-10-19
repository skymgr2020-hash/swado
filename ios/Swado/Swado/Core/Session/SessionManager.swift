import Foundation

/// Single source of truth for "who is logged in right now". A 401 anywhere
/// should route through clearSession() to force back to login, matching the
/// backend's "token never expires, only a 401 signals it was revoked" contract.
/// isLoggedIn is @Published (not just a computed Keychain check) so SwiftUI
/// root routing reacts immediately to login/logout without a manual refresh.
@MainActor
final class SessionManager: ObservableObject {
    @Published private(set) var currentUser: UserDTO?
    @Published private(set) var isLoggedIn: Bool

    init() {
        isLoggedIn = KeychainStore.shared.token != nil
    }

    func startSession(token: String, user: UserDTO) {
        KeychainStore.shared.token = token
        currentUser = user
        isLoggedIn = true
    }

    func updateUser(_ user: UserDTO) {
        currentUser = user
    }

    func clearSession() {
        KeychainStore.shared.token = nil
        currentUser = nil
        isLoggedIn = false
    }
}
