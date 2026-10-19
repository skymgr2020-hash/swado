import Foundation

/// Shared across the auth screens' view models — small enough not to need a
/// per-screen variant. Mirrors Android's AuthUiState.
enum AuthUIState: Equatable {
    case idle
    case loading
    case success
    case error(String)
}
