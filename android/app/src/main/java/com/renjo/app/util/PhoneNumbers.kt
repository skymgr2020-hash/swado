package com.renjo.app.util

/**
 * Firebase Phone Auth requires strict E.164 ("+<countrycode><number>", no
 * spaces/dashes) — unlike the backend's custom OTP endpoints, which accept
 * loose input and normalize server-side (see docs/api-contract/auth.md).
 * Applied once at phone-entry time so the normalized value threads through
 * nav args and is what's shown back to the user ("code sent to +91...").
 */
fun normalizePhoneToE164(rawPhone: String, defaultCountryCode: String?): String {
    val digitsAndPlus = rawPhone.trim().filter { it.isDigit() || it == '+' }
    if (digitsAndPlus.startsWith("+")) return digitsAndPlus
    val countryCode = defaultCountryCode?.takeIf { it.startsWith("+") } ?: "+91"
    return countryCode + digitsAndPlus
}
