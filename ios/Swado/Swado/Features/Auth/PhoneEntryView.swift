import SwiftUI

struct PhoneEntryView: View {
    @StateObject var viewModel: PhoneEntryViewModel
    var onOtpSent: (String) -> Void
    var onCompleted: (OtpVerifyResult, String) -> Void

    @State private var phone = ""

    private var title: String {
        switch viewModel.flow {
        case AuthFlow.signup: return "Create your account"
        case AuthFlow.forgotPassword: return "Reset your password"
        default: return "Verify your phone"
        }
    }

    private var subtitle: String? {
        switch viewModel.flow {
        case AuthFlow.signup: return "We'll text you a code to verify your number first."
        case AuthFlow.forgotPassword: return "Enter the phone number on your account to receive a reset code."
        default: return nil
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text(title).font(.largeTitle.bold())
            if let subtitle {
                Text(subtitle).foregroundStyle(.secondary)
            }
            TextField("Phone number", text: $phone)
                .keyboardType(.phonePad)
                .textFieldStyle(.roundedBorder)

            Button {
                Task {
                    if let result = await viewModel.sendOtp(phone: phone) {
                        switch result {
                        case .codeSent(let resultPhone): onOtpSent(resultPhone)
                        case .completed(let outcome, let resultPhone): onCompleted(outcome, resultPhone)
                        }
                    }
                }
            } label: {
                Text("Send OTP").frame(maxWidth: .infinity)
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
