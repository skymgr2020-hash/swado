package com.renjo.app.ui.screens.dining

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted

/** Date/time are plain "YYYY-MM-DD" / "HH:mm" text fields rather than native
 * picker dialogs — a deliberate scope reduction (documented, not silent) to
 * keep this screen shippable; swapping in DatePickerDialog/TimePickerDialog
 * is a follow-up, not a blocker for a working booking round-trip. */
@Composable
fun BookTableScreen(
    viewModel: DiningViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onBooked: () -> Unit,
) {
    val state by viewModel.bookState.collectAsState()
    var selectedRestaurant by remember { mutableStateOf<RestaurantDto?>(null) }
    var date by remember { mutableStateOf("") }
    var time by remember { mutableStateOf("") }
    var guests by remember { mutableStateOf("2") }
    var celebration by remember { mutableStateOf("") }
    var notes by remember { mutableStateOf("") }

    LaunchedEffect(Unit) { viewModel.loadDiningRestaurants() }
    LaunchedEffect(state) {
        if (state is BookTableUiState.Success) onBooked()
    }

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back") }
                Text("Book a Table", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }

            Column(modifier = Modifier.padding(horizontal = 20.dp)) {
                Text("Restaurant", color = Ink, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                when (val s = state) {
                    is BookTableUiState.LoadingRestaurants -> CircularProgressIndicator(modifier = Modifier.padding(top = 8.dp).height(20.dp))
                    is BookTableUiState.RestaurantsReady -> {
                        Column(modifier = Modifier.padding(top = 8.dp)) {
                            s.restaurants.forEach { restaurant ->
                                val selected = selectedRestaurant?.id == restaurant.id
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 3.dp)
                                        .border(
                                            1.dp,
                                            if (selected) MaterialTheme.colorScheme.primary else Line,
                                            RoundedCornerShape(10.dp),
                                        )
                                        .clickable { selectedRestaurant = restaurant }
                                        .padding(12.dp),
                                ) {
                                    Text(restaurant.name, color = Ink, fontSize = 13.sp, modifier = Modifier.weight(1f))
                                }
                            }
                        }
                    }
                    else -> {}
                }
                Spacer(modifier = Modifier.height(12.dp))
                OutlinedTextField(value = date, onValueChange = { date = it }, label = { Text("Date (YYYY-MM-DD)") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = time, onValueChange = { time = it }, label = { Text("Time (HH:mm)") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = guests, onValueChange = { guests = it.filter { c -> c.isDigit() } }, label = { Text("Guests") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = celebration, onValueChange = { celebration = it }, label = { Text("Celebration (optional)") }, modifier = Modifier.fillMaxWidth())
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = notes, onValueChange = { notes = it }, label = { Text("Special requests (optional)") }, modifier = Modifier.fillMaxWidth())

                if (state is BookTableUiState.Error) {
                    Text((state as BookTableUiState.Error).message, color = Danger, fontSize = 12.sp, modifier = Modifier.padding(top = 8.dp))
                }

                Button(
                    onClick = {
                        val restaurantId = selectedRestaurant?.id ?: return@Button
                        viewModel.bookTable(restaurantId, date, time, guests.toIntOrNull() ?: 2, celebration.ifBlank { null }, notes.ifBlank { null })
                    },
                    enabled = selectedRestaurant != null && date.isNotBlank() && time.isNotBlank() && state !is BookTableUiState.Booking,
                    modifier = Modifier.fillMaxWidth().padding(top = 16.dp, bottom = 24.dp),
                ) {
                    if (state is BookTableUiState.Booking) {
                        CircularProgressIndicator(modifier = Modifier.height(20.dp))
                    } else {
                        Text("Confirm Booking", fontWeight = FontWeight.Black)
                    }
                }
            }
        }
    }
}
