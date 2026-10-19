package com.renjo.app.ui.screens.splash

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.R
import com.renjo.app.data.network.dto.BrandingDto

/**
 * Pixel-matched to the Flutter app's _SplashView (lib/screens/app_splash_screen.dart):
 * solid #FC5F01 background (hardcoded there, not branding-driven — matched
 * exactly, not dynamically), splash.png wordmark at 230x110dp, white 82%-alpha
 * bold 14sp tagline, 26dp white spinner, minimum 5s display handled in the ViewModel.
 */
private val SplashBackground = Color(0xFFFC5F01)

@Composable
fun SplashScreen(
    viewModel: SplashViewModel = hiltViewModel(),
    onBrandingReady: (BrandingDto) -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsState()

    Surface(modifier = Modifier.fillMaxSize(), color = SplashBackground) {
        when (val state = uiState) {
            is SplashUiState.Loading -> SplashVisual()
            is SplashUiState.Loaded -> {
                onBrandingReady(state.branding)
                SplashVisual()
            }
            is SplashUiState.Error -> ErrorContent(state.message, onRetry = viewModel::loadBranding)
        }
    }
}

@Composable
private fun SplashVisual() {
    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Image(
            painter = painterResource(R.drawable.splash_logo),
            contentDescription = "Swado",
            contentScale = ContentScale.Fit,
            modifier = Modifier.width(230.dp).height(110.dp),
        )
        Spacer(modifier = Modifier.height(18.dp))
        Text(
            text = "Fresh food, fast delivery",
            color = Color.White.copy(alpha = 0.82f),
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
        )
        Spacer(modifier = Modifier.height(34.dp))
        CircularProgressIndicator(
            modifier = Modifier.size(26.dp),
            strokeWidth = 2.6.dp,
            color = Color.White,
        )
    }
}

@Composable
private fun ErrorContent(message: String, onRetry: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(text = "Couldn't reach the server", color = Color.White, fontWeight = FontWeight.Bold)
        Text(text = message, color = Color.White.copy(alpha = 0.82f), fontSize = 12.sp)
        TextButton(onClick = onRetry) { Text("Retry", color = Color.White) }
    }
}
