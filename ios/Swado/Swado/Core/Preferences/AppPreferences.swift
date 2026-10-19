import Foundation

/// Matches Android's AppPreferences (DataStore) role — small flags, not the
/// TTL discovery cache (that's DiskCache, added in a later phase).
final class AppPreferences {
    private let defaults = UserDefaults.standard
    private let onboardingSeenKey = "onboarding_seen"

    var hasSeenOnboarding: Bool {
        get { defaults.bool(forKey: onboardingSeenKey) }
        set { defaults.set(newValue, forKey: onboardingSeenKey) }
    }
}
