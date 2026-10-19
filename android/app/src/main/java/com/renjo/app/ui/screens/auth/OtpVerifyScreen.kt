package com.renjo.app.ui.screens.auth

import android.app.Activity
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.R
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted

private const val OTP_LENGTH = 6

/**
 * Pixel-matched to the Flutter app's OtpVerificationScreen: back button, app
 * logo, shield badge, "Verify Your Number" title, phone + Edit link, 6
 * individual OTP boxes, resend-with-countdown row, fixed bottom CTA.
 */
@Composable
fun OtpVerifyScreen(
    viewModel: OtpVerifyViewModel = hiltViewModel(),
    onBack: () -> Unit = {},
    onResult: (OtpVerifyResult) -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    val secondsRemaining by viewModel.secondsRemaining.collectAsState()
    var code by remember { mutableStateOf("") }
    val activity = LocalContext.current as? Activity
    val focusRequester = remember { FocusRequester() }

    LaunchedEffect(Unit) { focusRequester.requestFocus() }

    Surface(modifier = Modifier.fillMaxSize()) {
        Box(modifier = Modifier.fillMaxSize()) {
            Column(
                modifier = Modifier.fillMaxSize().padding(horizontal = 28.dp).padding(bottom = 98.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Row(modifier = Modifier.fillMaxWidth()) {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color.Black)
                    }
                }
                Spacer(modifier = Modifier.height(16.dp))
                Image(
                    painter = painterResource(R.drawable.app_logo),
                    contentDescription = "Swado",
                    contentScale = ContentScale.Fit,
                    modifier = Modifier.width(214.dp).height(104.dp),
                )
                Spacer(modifier = Modifier.height(26.dp))
                ShieldBadge()
                Spacer(modifier = Modifier.height(26.dp))
                Text(
                    text = "Verify Your Number",
                    fontSize = 20.sp,
                    fontWeight = androidx.compose.ui.text.font.FontWeight.Black,
                    color = Ink,
                )
                Spacer(modifier = Modifier.height(14.dp))
                Text(
                    text = "Enter the $OTP_LENGTH-digit code sent to",
                    fontSize = 13.sp,
                    color = Muted,
                    textAlign = TextAlign.Center,
                )
                Spacer(modifier = Modifier.height(8.dp))
                Row {
                    Text(text = viewModel.phone, fontSize = 13.sp, fontWeight = androidx.compose.ui.text.font.FontWeight.Black, color = Ink)
                    Spacer(modifier = Modifier.width(10.dp))
                    Text(
                        text = "Edit",
                        fontSize = 13.sp,
                        fontWeight = androidx.compose.ui.text.font.FontWeight.Black,
                        color = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.clickable(onClick = onBack),
                    )
                }
                Spacer(modifier = Modifier.height(30.dp))

                Box {
                    // Real input lives in an invisible field so the platform
                    // keyboard/autofill work normally; the boxes below are
                    // purely a visual reflection of its value.
                    BasicTextField(
                        value = code,
                        onValueChange = { code = it.filter(Char::isDigit).take(OTP_LENGTH) },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
                        textStyle = androidx.compose.ui.text.TextStyle(color = Color.Transparent, fontSize = 1.sp),
                        cursorBrush = SolidColor(Color.Transparent),
                        modifier = Modifier.focusRequester(focusRequester).size(1.dp),
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        repeat(OTP_LENGTH) { index ->
                            val digit = code.getOrNull(index)?.toString().orEmpty()
                            val active = index == code.length
                            OtpBox(digit = digit, active = active)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(28.dp))
                ResendRow(
                    secondsRemaining = secondsRemaining,
                    onResend = { viewModel.resend(activity) },
                )

                if (uiState is AuthUiState.Error) {
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(text = (uiState as AuthUiState.Error).message, color = Danger, fontSize = 13.sp, textAlign = TextAlign.Center)
                }
            }

            VerifyButton(
                loading = uiState is AuthUiState.Loading,
                onClick = { viewModel.verify(code, onResult) },
                modifier = Modifier.align(Alignment.BottomCenter).fillMaxWidth().padding(horizontal = 28.dp).padding(bottom = 14.dp),
            )
        }
    }
}

@Composable
private fun OtpBox(digit: String, active: Boolean) {
    Box(
        modifier = Modifier
            .width(46.dp)
            .height(58.dp)
            .clip(RoundedCornerShape(8.dp))
            .background(Color.White)
            .border(
                width = if (active) 1.5.dp else 1.2.dp,
                color = if (active) MaterialTheme.colorScheme.primary else Line,
                shape = RoundedCornerShape(8.dp),
            ),
        contentAlignment = Alignment.Center,
    ) {
        Text(text = digit, fontSize = 20.sp, fontWeight = androidx.compose.ui.text.font.FontWeight.Bold, color = Ink)
    }
}

@Composable
private fun ShieldBadge() {
    Box(
        modifier = Modifier.size(104.dp).clip(CircleShape).background(Color(0xFFE5F5EC)),
        contentAlignment = Alignment.Center,
    ) {
        Box(
            modifier = Modifier.size(66.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primary),
            contentAlignment = Alignment.Center,
        ) {
            Icon(Icons.Filled.Lock, contentDescription = null, tint = Color.White, modifier = Modifier.size(30.dp))
        }
    }
}

@Composable
private fun ResendRow(secondsRemaining: Int, onResend: () -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Text(text = "Didn't receive the code?", color = Muted, fontSize = 13.sp)
        Spacer(modifier = Modifier.width(9.dp))
        Text(
            text = "Resend",
            color = MaterialTheme.colorScheme.primary,
            fontSize = 13.sp,
            fontWeight = androidx.compose.ui.text.font.FontWeight.Black,
            modifier = Modifier.clickable { if (secondsRemaining == 0) onResend() },
        )
        if (secondsRemaining > 0) {
            Spacer(modifier = Modifier.width(6.dp))
            Text(text = "in", color = Muted, fontSize = 13.sp)
            Spacer(modifier = Modifier.width(6.dp))
            Text(text = "00:%02d".format(secondsRemaining), color = Muted, fontSize = 13.sp)
        }
    }
}

@Composable
private fun VerifyButton(loading: Boolean, onClick: () -> Unit, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .height(58.dp)
            .clip(RoundedCornerShape(8.dp))
            .background(if (loading) MaterialTheme.colorScheme.primary.copy(alpha = 0.6f) else MaterialTheme.colorScheme.primary)
            .clickable { if (!loading) onClick() },
        contentAlignment = Alignment.Center,
    ) {
        if (loading) {
            CircularProgressIndicator(modifier = Modifier.size(22.dp), color = Color.White, strokeWidth = 2.2.dp)
        } else {
            Text(text = "Verify & Continue", color = Color.White, fontSize = 16.sp, fontWeight = androidx.compose.ui.text.font.FontWeight.Black)
        }
    }
}
