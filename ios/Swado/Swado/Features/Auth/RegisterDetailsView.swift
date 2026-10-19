import SwiftUI

struct RegisterDetailsView: View {
    @StateObject var viewModel: RegisterDetailsViewModel
    var onRegistered: () -> Void

    @State private var name = ""
    @State private var email = ""

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Tell us about you").font(.largeTitle.bold())
            Text("Phone verified: \(viewModel.phone)")
                .font(.footnote)
                .foregroundStyle(.secondary)

            TextField("Full name", text: $name)
                .textFieldStyle(.roundedBorder)
            TextField("Email (optional)", text: $email)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .textFieldStyle(.roundedBorder)

            Button {
                Task {
                    if await viewModel.register(name: name, email: email) {
                        onRegistered()
                    }
                }
            } label: {
                Text("Create account").frame(maxWidth: .infinity)
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
