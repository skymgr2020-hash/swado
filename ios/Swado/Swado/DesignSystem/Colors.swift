import SwiftUI

enum BrandColors {
    // Fallbacks used before /app/branding responds, or if a color field is
    // null. Matches the live values captured from the real backend.
    static let defaultPrimary = Color(hex: "#2563EB")
    static let defaultSecondary = Color(hex: "#1D4ED8")

    // Semantic neutrals ported 1:1 from the Flutter app's FoodFlowTheme
    // (customer/lib/theme/foodflow_theme.dart) so text/border/status colors
    // match exactly regardless of the dynamic brand primary/secondary.
    static let ink = Color(hex: "#111827")
    static let inkSoft = Color(hex: "#374151")
    static let muted = Color(hex: "#6B7280")
    static let faint = Color(hex: "#9CA3AF")
    static let line = Color(hex: "#E5E7EB")
    static let canvas = Color(hex: "#F8F8F8")
    static let success = Color(hex: "#22C55E")
    static let danger = Color(hex: "#E53935")
}

extension Color {
    init(hex: String) {
        let cleaned = hex.trimmingCharacters(in: CharacterSet(charactersIn: "#"))
        var value: UInt64 = 0
        Scanner(string: cleaned).scanHexInt64(&value)
        let r = Double((value >> 16) & 0xFF) / 255
        let g = Double((value >> 8) & 0xFF) / 255
        let b = Double(value & 0xFF) / 255
        self.init(red: r, green: g, blue: b)
    }
}
