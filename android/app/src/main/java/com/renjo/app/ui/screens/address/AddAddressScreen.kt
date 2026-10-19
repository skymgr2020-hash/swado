package com.renjo.app.ui.screens.address

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink

@Composable
fun AddAddressScreen(
    viewModel: AddAddressViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onSaved: () -> Unit,
) {
    val saving by viewModel.saving.collectAsState()
    val error by viewModel.error.collectAsState()

    var label by remember { mutableStateOf("Home") }
    var addressLine by remember { mutableStateOf("") }
    var city by remember { mutableStateOf("") }
    var state by remember { mutableStateOf("") }
    var pincode by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back") }
                Text("Add Address", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }
            Column(modifier = Modifier.padding(horizontal = 20.dp)) {
                OutlinedTextField(value = label, onValueChange = { label = it }, label = { Text("Label (Home, Work...)") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = addressLine, onValueChange = { addressLine = it }, label = { Text("Address") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                Row {
                    OutlinedTextField(value = city, onValueChange = { city = it }, label = { Text("City") }, modifier = Modifier.weight(1f))
                    Spacer(modifier = Modifier.width(8.dp))
                    OutlinedTextField(value = state, onValueChange = { state = it }, label = { Text("State") }, modifier = Modifier.weight(1f))
                }
                Spacer(modifier = Modifier.height(8.dp))
                Row {
                    OutlinedTextField(value = pincode, onValueChange = { pincode = it }, label = { Text("Pincode") }, modifier = Modifier.weight(1f))
                    Spacer(modifier = Modifier.width(8.dp))
                    OutlinedTextField(value = phone, onValueChange = { phone = it }, label = { Text("Phone") }, modifier = Modifier.weight(1f))
                }
                Spacer(modifier = Modifier.height(8.dp))
                error?.let { Text(it, color = Danger, fontSize = 12.sp) }
                Button(
                    onClick = { viewModel.save(label, addressLine, city, state, pincode, phone, onSaved) },
                    enabled = !saving,
                    modifier = Modifier.fillMaxWidth().padding(top = 12.dp, bottom = 24.dp),
                ) {
                    if (saving) CircularProgressIndicator(modifier = Modifier.width(20.dp)) else Text("Save Address", fontWeight = FontWeight.Black)
                }
            }
        }
    }
}
