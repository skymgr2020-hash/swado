package com.renjo.app.ui.screens.dining

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.DiningBookingDto
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted

@Composable
fun DiningListScreen(
    viewModel: DiningViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onNewBooking: () -> Unit,
) {
    val state by viewModel.listState.collectAsState()

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(modifier = Modifier.fillMaxSize()) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back") }
                Text("Table Bookings", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }
            Button(onClick = onNewBooking, modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp)) {
                Text("Book a Table")
            }
            Spacer(modifier = Modifier.height(12.dp))

            when (val s = state) {
                is DiningListUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                is DiningListUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(s.message, color = Danger)
                }
                is DiningListUiState.Loaded -> {
                    if (s.bookings.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No bookings yet", color = Muted)
                        }
                    } else {
                        LazyColumn(contentPadding = PaddingValues(16.dp)) {
                            items(s.bookings, key = { it.id }) { booking ->
                                BookingRow(booking)
                                Spacer(modifier = Modifier.height(10.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun BookingRow(booking: DiningBookingDto) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(Color.White, RoundedCornerShape(14.dp))
            .padding(14.dp),
    ) {
        Text(booking.restaurant?.name ?: "Booking #${booking.id}", color = Ink, fontWeight = FontWeight.Bold, fontSize = 14.sp)
        Text("${booking.bookingDate ?: ""} • ${booking.bookingTime ?: ""}", color = Muted, fontSize = 12.sp)
        Text("${booking.numberOfGuests ?: 0} guests", color = Muted, fontSize = 12.sp)
        Text(
            booking.status?.replaceFirstChar { it.uppercase() } ?: "",
            color = Ink,
            fontWeight = FontWeight.Bold,
            fontSize = 12.sp,
            modifier = Modifier.padding(top = 4.dp),
        )
    }
}
