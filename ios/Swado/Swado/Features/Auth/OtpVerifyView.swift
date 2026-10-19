import SwiftUI

struct OtpVerifyView: View {
    @StateObject var viewModel: OtpVerifyViewModel
    var onResult: (OtpVerifyResult) -> Void

    @State private var code = ""

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Enter the code").font(.largeTitle.bold())
            Text("We sent a verification code to \(viewModel.phone)")
                .foregroundStyle(.secondary)

            TextField("Verification code", text: $code)
                .keyboardType(.numberPad)
                .textFieldStyle(.roundedBorder)

            Button {
                Task {
                    if let result = await viewModel.verify(code: code) {
                        onResult(result)
                    }
                }
            } label: {
                Text("Verify").frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .disabled(viewModel.state == .loading)

            Button("Resend code") {
                Task { await viewModel.resend() }
            }
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
