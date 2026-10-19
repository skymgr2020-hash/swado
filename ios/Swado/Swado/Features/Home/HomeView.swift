import SwiftUI

struct HomeView: View {
    @StateObject var viewModel: HomeViewModel
    @ObservedObject var sessionManager: SessionManager
    var onLoggedOut: () -> Void

    var body: some View {
        VStack(spacing: 8) {
            Spacer()
            Text("You're logged in").font(.largeTitle.bold())
            Text(sessionManager.currentUser?.name ?? "…").font(.title3)
            if let phone = sessionManager.currentUser?.phone {
                Text(phone).foregroundStyle(.secondary)
            }
            if let email = sessionManager.currentUser?.email {
                Text(email).foregroundStyle(.secondary)
            }
            Spacer()
            Button {
                Task {
                    await viewModel.logout()
                    onLoggedOut()
                }
            } label: {
                Text("Log out").frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
        }
        .padding(24)
    }
}
