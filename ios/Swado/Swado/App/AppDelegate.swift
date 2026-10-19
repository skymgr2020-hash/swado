import UIKit
import FirebaseCore

/// UIApplicationDelegateAdaptor is required (not just SwiftUI's App lifecycle)
/// because later phases hook APNs registration (Phase 6 push) and Pusher/FCM
/// lifecycle callbacks here, which only fire through the UIKit delegate.
final class AppDelegate: NSObject, UIApplicationDelegate {
    func application(
        _ application: UIApplication,
        didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil
    ) -> Bool {
        FirebaseApp.configure()
        return true
    }
}
