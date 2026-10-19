import Foundation

/// Firebase Phone Auth requires strict E.164 ("+<countrycode><number>", no
/// spaces/dashes) — unlike the backend's custom OTP endpoints, which accept
/// loose input and normalize server-side (see docs/api-contract/auth.md).
/// Applied once at phone-entry time so the normalized value threads through
/// navigation and is what's shown back to the user ("code sent to +91...").
func normalizePhoneToE164(_ rawPhone: String, defaultCountryCode: String?) -> String {
    let digitsAndPlus = rawPhone.trimmingCharacters(in: .whitespaces).filter { $0.isNumber || $0 == "+" }
    if digitsAndPlus.hasPrefix("+") { return digitsAndPlus }
    let countryCode = (defaultCountryCode?.hasPrefix("+") == true) ? defaultCountryCode! : "+91"
    return countryCode + digitsAndPlus
}
