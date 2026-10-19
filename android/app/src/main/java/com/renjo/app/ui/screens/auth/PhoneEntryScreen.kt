package com.renjo.app.ui.screens.auth

import android.app.Activity
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.ui.navigation.AuthFlow

@Composable
fun PhoneEntryScreen(
    viewModel: PhoneEntryViewModel = hiltViewModel(),
    onOtpSent: (phone: String) -> Unit,
    onCompleted: (PhoneEntryResult.Completed) -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    var phone by remember { mutableStateOf("") }
    val activity = LocalContext.current as? Activity

    val title = when (viewModel.flow) {
        AuthFlow.SIGNUP -> "Create your account"
        AuthFlow.FORGOT_PASSWORD -> "Reset your password"
        else -> "Verify your phone"
    }
    val subtitle = when (viewModel.flow) {
        AuthFlow.SIGNUP -> "We'll text you a code to verify your number first."
        AuthFlow.FORGOT_PASSWORD -> "Enter the phone number on your account to receive a reset code."
        else -> null
    }

    Surface(modifier = Modifier.fillMaxSize()) {
        Column(modifier = Modifier.fillMaxSize().padding(24.dp)) {
            Text(text = title, style = MaterialTheme.typography.headlineMedium)
            subtitle?.let {
                Text(text = it, style = MaterialTheme.typography.bodyMedium, modifier = Modifier.padding(top = 8.dp))
            }
            OutlinedTextField(
                value = phone,
                onValueChange = { phone = it },
                label = { Text("Phone number") },
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                modifier = Modifier.fillMaxWidth().padding(top = 24.dp),
            )
            Button(
                onClick = {
                    viewModel.sendOtp(phone, activity) { result ->
                        when (result) {
                            is PhoneEntryResult.CodeSent -> onOtpSent(result.phone)
                            is PhoneEntryResult.Completed -> onCompleted(result)
                        }
                    }
                },
                modifier = Modifier.fillMaxWidth().padding(top = 16.dp),
                enabled = uiState !is AuthUiState.Loading,
            ) { Text("Send OTP") }

            if (uiState is AuthUiState.Error) {
                Text(
                    text = (uiState as AuthUiState.Error).message,
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.padding(top = 16.dp),
                )
            }
        }
    }
}
