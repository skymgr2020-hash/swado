import SwiftUI

struct LoginView: View {
    @StateObject var viewModel: LoginViewModel
    var onLoggedIn: () -> Void
    var onContinueWithPhone: () -> Void
    var onNavigateToSignup: () -> Void
    var onNavigateToForgotPassword: () -> Void

    @State private var selectedTab = 0
    @State private var email = ""
    @State private var password = ""

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Log in").font(.largeTitle.bold())

            Picker("", selection: $selectedTab) {
                Text("Phone").tag(0)
                Text("Email").tag(1)
            }
            .pickerStyle(.segmented)

            if selectedTab == 0 {
                Text("We'll text you a one-time code to log in — no password needed.")
                    .foregroundStyle(.secondary)

                Button {
                    onContinueWithPhone()
                } label: {
                    Text("Continue with phone").frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            } else {
                TextField("Email", text: $email)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .textFieldStyle(.roundedBorder)
                SecureField("Password", text: $password)
                    .textFieldStyle(.roundedBorder)

                Button("Forgot password?", action: onNavigateToForgotPassword)
                    .font(.footnote)

                Button {
                    Task {
                        if await viewModel.loginWithEmail(email: email, password: password) {
                            onLoggedIn()
                        }
                    }
                } label: {
                    Text("Log in").frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .disabled(viewModel.state == .loading)
            }

            if viewModel.state == .loading {
                ProgressView().frame(maxWidth: .infinity)
            } else if case .error(let message) = viewModel.state {
                Text(message).foregroundStyle(.red).font(.footnote)
            }

            Spacer()

            Button("New here? Create an account", action: onNavigateToSignup)
                .frame(maxWidth: .infinity)
        }
        .padding(24)
        .onChange(of: selectedTab) { _ in viewModel.clearError() }
    }
}
