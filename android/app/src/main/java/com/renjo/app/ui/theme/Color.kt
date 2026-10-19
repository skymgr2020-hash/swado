package com.renjo.app.ui.theme

import androidx.compose.ui.graphics.Color

// Fallbacks used before /app/branding responds, or if a color field is null.
// These match customer/lib/theme/brand_palette.dart; the splash keeps its
// Flutter-matched standalone orange background.
val DefaultPrimary = Color(0xFF8B5CF6)
val DefaultSecondary = Color(0xFF111827)
val SurfaceLight = Color(0xFFFAFAFA)
val OnSurfaceLight = Color(0xFF1B1B1F)

// Semantic neutrals ported 1:1 from the Flutter app's FoodFlowTheme
// (customer/lib/theme/foodflow_theme.dart) so text/border/status colors
// match exactly regardless of the dynamic brand primary/secondary.
val Ink = Color(0xFF111827)
val InkSoft = Color(0xFF374151)
val Muted = Color(0xFF6B7280)
val Faint = Color(0xFF9CA3AF)
val Line = Color(0xFFE5E7EB)
val Canvas = Color(0xFFF8F8F8)
val Success = Color(0xFF22C55E)
val SuccessDark = Color(0xFF168A35)
val Danger = Color(0xFFE53935)
val WarmTint = Color(0xFFFFF3E8)
val WarmTintBorder = Color(0xFFFFD7AF)
val CardBorder = Color(0xFFE9EDF3)

// Order status chip colors — ported 1:1 from orders_screen.dart's
// _statusStyle so status coloring matches the Flutter app exactly.
val StatusConfirmedFg = Color(0xFF2563EB)
val StatusConfirmedBg = Color(0xFFEAF2FF)
val StatusLiveFg = Color(0xFF11832D)
val StatusLiveBg = Color(0xFFE6F7E7)
val StatusDeliveredFg = Color(0xFF169B3A)
val StatusDeliveredBg = Color(0xFFE8F8EA)
val StatusCancelledFg = Color(0xFFE11D28)
val StatusCancelledBg = Color(0xFFFFECEC)

// Wallet-specific greens — close to but distinct from SuccessDark, ported
// from wallet_screen.dart's _WalletTransactionTile/_buildFilterChip.
val WalletCreditFg = Color(0xFF0E8F45)
val WalletCreditBg = Color(0xFFEFF9F2)
val WalletCreditBorder = Color(0xFF68B98A)
val WalletDebitBg = Color(0xFFFFF0EC)

fun parseHexColorOrNull(hex: String?): Color? {
    if (hex.isNullOrBlank()) return null
    return try {
        Color(android.graphics.Color.parseColor(hex))
    } catch (_: IllegalArgumentException) {
        null
    }
}
