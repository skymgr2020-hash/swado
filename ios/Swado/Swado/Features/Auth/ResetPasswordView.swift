import SwiftUI

struct ResetPasswordView: View {
    @StateObject var viewModel: ResetPasswordViewModel
    var onDone: () -> Void

    @State private var password = ""
    @State private var confirmPassword = ""

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Set a new password").font(.largeTitle.bold())

            SecureField("New password", text: $password)
                .textFieldStyle(.roundedBorder)
            SecureField("Confirm password", text: $confirmPassword)
                .textFieldStyle(.roundedBorder)

            Button {
                Task {
                    if await viewModel.resetPassword(password: password, confirmPassword: confirmPassword) {
                        onDone()
                    }
                }
            } label: {
                Text("Update password").frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .disabled(viewModel.state == .loading)

            if viewModel.state == .loading {
                ProgressView().frame(maxWidth: .infinity)
            } else if case .error(let message) = viewModel.state {
                Text(message).foregroundStyle(.red).font(.footnote)
            }

            Spacer()
        }
        .padding(24)
    }
}
