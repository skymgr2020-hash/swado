package com.renjo.app.ui.screens.auth

import android.app.Activity
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Divider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
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

/**
 * Pixel-matched to the Flutter app's LoginScreen (lib/screens/auth/login_screen.dart):
 * hero image, "Welcome Back!" headline, single phone field (no email/password
 * UI exists in the reference — phone status determines login vs signup for
 * you), optional Google button, Terms/Privacy text.
 */
@Composable
fun LoginScreen(
    viewModel: LoginViewModel = hiltViewModel(),
    onOtpRequired: (phone: String, flow: String) -> Unit,
    onCompleted: (LoginContinueResult.Completed) -> Unit,
    onGoogleSignIn: () -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsState()
    val showGoogleButton by viewModel.showGoogleButton.collectAsState()
    var phone by remember { mutableStateOf("") }
    val activity = LocalContext.current as? Activity

    Surface(modifier = Modifier.fillMaxSize()) {
        Column(modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
            Image(
                painter = painterResource(R.drawable.login_hero_background),
                contentDescription = null,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxWidth().height(340.dp),
            )

            Column(modifier = Modifier.padding(horizontal = 28.dp).padding(top = 16.dp)) {
                Text(
                    text = "Welcome Back!",
                    fontSize = 26.sp,
                    fontWeight = FontWeight.Black,
                    color = Ink,
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = "Login to continue your deliveries",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium,
                    color = Muted,
                )
                Spacer(modifier = Modifier.height(30.dp))

                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    placeholder = { Text("Enter your mobile number") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(unfocusedBorderColor = Line),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(modifier = Modifier.height(18.dp))

                Button(
                    onClick = {
                        viewModel.continueWithPhone(phone, activity) { result ->
                            when (result) {
                                is LoginContinueResult.OtpRequired -> onOtpRequired(result.phone, result.flow)
                                is LoginContinueResult.Completed -> onCompleted(result)
                            }
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(58.dp),
                    enabled = uiState !is AuthUiState.Loading,
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                ) {
                    if (uiState is AuthUiState.Loading) {
                        CircularProgressIndicator(modifier = Modifier.size(20.dp), color = Color.White, strokeWidth = 2.dp)
                    } else {
                        Text("Continue", fontSize = 17.sp, fontWeight = FontWeight.ExtraBold)
                    }
                }

                if (uiState is AuthUiState.Error) {
                    Text(
                        text = (uiState as AuthUiState.Error).message,
                        color = Danger,
                        fontSize = 13.sp,
                        modifier = Modifier.padding(top = 12.dp),
                    )
                }

                if (showGoogleButton) {
                    Spacer(modifier = Modifier.height(20.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Divider(modifier = Modifier.weight(1f), color = Line)
                        Text("or", color = Muted, fontSize = 13.sp, modifier = Modifier.padding(horizontal = 18.dp))
                        Divider(modifier = Modifier.weight(1f), color = Line)
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    OutlinedButton(
                        onClick = onGoogleSignIn,
                        modifier = Modifier.fillMaxWidth().height(58.dp),
                    ) {
                        Image(
                            painter = painterResource(R.drawable.ic_google_logo),
                            contentDescription = null,
                            modifier = Modifier.width(22.dp).height(22.dp),
                        )
                        Spacer(modifier = Modifier.width(12.dp))
                        Text("Continue with Google", fontSize = 13.sp, fontWeight = FontWeight.ExtraBold, color = Ink)
                    }
                }

                Spacer(modifier = Modifier.height(32.dp))
                Text(
                    text = "By continuing, you agree to our Terms of Service and our Privacy Policy",
                    fontSize = 13.sp,
                    color = Muted,
                    textAlign = TextAlign.Center,
                    modifier = Modifier.fillMaxWidth().padding(bottom = 24.dp),
                )
            }
        }
    }
}
