package com.renjo.app.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

private val SwadoTypography = Typography(
    headlineMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.ExtraBold,
        fontSize = 24.sp,
        lineHeight = 30.sp,
    ),
    headlineSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.ExtraBold,
        fontSize = 20.sp,
        lineHeight = 26.sp,
    ),
    titleLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.ExtraBold,
        fontSize = 18.sp,
        lineHeight = 24.sp,
    ),
    titleMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 15.sp,
        lineHeight = 20.sp,
    ),
    bodyMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 13.sp,
        lineHeight = 18.sp,
    ),
    bodySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
    labelLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 14.sp,
        lineHeight = 18.sp,
    ),
    labelMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
)
/**
 * [brandPrimary]/[brandSecondary] come from the live branding response
 * (BrandingDto.primaryColor/secondaryColor) once it loads; the splash screen
 * renders with the fallback tokens for the brief moment before that.
 * Neutral/status colors (Ink, Muted, Line, Canvas, Danger, Success) are
 * ported 1:1 from the Flutter app's FoodFlowTheme regardless of branding.
 */
@Composable
fun SwadoTheme(
    brandPrimary: androidx.compose.ui.graphics.Color? = null,
    brandSecondary: androidx.compose.ui.graphics.Color? = null,
    content: @Composable () -> Unit,
) {
    val primary = brandPrimary ?: DefaultPrimary
    val secondary = brandSecondary ?: DefaultSecondary
    val colorScheme = if (isSystemInDarkTheme()) {
        darkColorScheme(primary = primary, secondary = secondary, error = Danger)
    } else {
        lightColorScheme(
            primary = primary,
            secondary = secondary,
            background = Canvas,
            onBackground = Ink,
            surface = SurfaceLight,
            onSurface = Ink,
            onSurfaceVariant = Muted,
            outline = Line,
            error = Danger,
        )
    }
    MaterialTheme(colorScheme = colorScheme, typography = SwadoTypography, content = content)
}
