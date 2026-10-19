import 'package:flutter/material.dart';

class OnboardColors {
  static const primary = Color(0xFF2563EB);
  static const secondary = Color(0xFF1D4ED8);
  static const accent = Color(0xFF14B8A6);
  static const warning = Color(0xFFF59E0B);
  static const danger = Color(0xFFE8335A);
  static const success = Color(0xFF22C55E);

  static const ink = Color(0xFF172033);
  static const inkSoft = Color(0xFF334155);
  static const muted = Color(0xFF718096);
  static const faint = Color(0xFFA7B0BE);
  static const line = Color(0xFFE3E8F0);
  static const canvas = Color(0xFFEEF1F8);
  static const surface = Colors.white;

  static final glassSurface = Colors.white.withValues(alpha: 0.76);
  static final glassBorder = Colors.white.withValues(alpha: 0.82);

  static const auroraA = Color(0xFF7FA8FF);
  static const auroraB = Color(0xFFFFB98A);
  static const auroraC = Color(0xFFBFA0FF);

  static const gradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, secondary],
  );
}

class OnboardTheme {
  static ThemeData build() {
    final scheme = ColorScheme.fromSeed(
      seedColor: OnboardColors.primary,
      primary: OnboardColors.primary,
      secondary: OnboardColors.accent,
      surface: OnboardColors.surface,
      error: OnboardColors.danger,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: OnboardColors.canvas,
      canvasColor: OnboardColors.canvas,
      visualDensity: VisualDensity.standard,
      fontFamily: 'Arial',
      textTheme: const TextTheme(
        headlineMedium: TextStyle(
          color: OnboardColors.ink,
          fontSize: 28,
          fontWeight: FontWeight.w900,
        ),
        titleLarge: TextStyle(
          color: OnboardColors.ink,
          fontSize: 20,
          fontWeight: FontWeight.w900,
        ),
        titleMedium: TextStyle(
          color: OnboardColors.ink,
          fontSize: 16,
          fontWeight: FontWeight.w800,
        ),
        bodyMedium: TextStyle(color: OnboardColors.inkSoft, fontSize: 14),
        bodySmall: TextStyle(color: OnboardColors.muted, fontSize: 12),
      ),
      appBarTheme: const AppBarTheme(
        elevation: 0,
        centerTitle: false,
        backgroundColor: Colors.transparent,
        foregroundColor: OnboardColors.ink,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: TextStyle(
          color: OnboardColors.ink,
          fontSize: 18,
          fontWeight: FontWeight.w900,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: OnboardColors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(
            color: OnboardColors.primary,
            width: 1.4,
          ),
        ),
        filled: true,
        fillColor: const Color(0xFFF7F8FC),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        prefixIconColor: OnboardColors.primary,
        suffixIconColor: OnboardColors.muted,
        labelStyle: const TextStyle(
          color: OnboardColors.muted,
          fontWeight: FontWeight.w800,
        ),
        hintStyle: const TextStyle(color: OnboardColors.faint),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: OnboardColors.primary,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(54),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: OnboardColors.ink,
          side: const BorderSide(color: OnboardColors.primary),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          textStyle: const TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        margin: EdgeInsets.zero,
        color: OnboardColors.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: Colors.white.withValues(alpha: 0.86),
        selectedColor: OnboardColors.primary.withValues(alpha: 0.12),
        labelStyle: const TextStyle(
          color: OnboardColors.ink,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
        side: const BorderSide(color: OnboardColors.line),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.transparent,
        indicatorColor: OnboardColors.primary.withValues(alpha: 0.14),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            color: states.contains(WidgetState.selected)
                ? OnboardColors.primary
                : OnboardColors.muted,
            fontSize: 12,
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w900
                : FontWeight.w600,
          ),
        ),
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            color: states.contains(WidgetState.selected)
                ? OnboardColors.primary
                : OnboardColors.muted,
          ),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: OnboardColors.primary,
        contentTextStyle: const TextStyle(color: Colors.white),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      dividerTheme: const DividerThemeData(color: OnboardColors.line, space: 1),
    );
  }
}
