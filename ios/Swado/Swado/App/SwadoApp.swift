import SwiftUI

@main
struct SwadoApp: App {
    @UIApplicationDelegateAdaptor(AppDelegate.self) private var appDelegate
    private let dependencies = AppDependencies()

    var body: some Scene {
        WindowGroup {
            SwadoRootView(dependencies: dependencies)
        }
    }
}
