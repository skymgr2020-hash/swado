package com.renjo.app.ui.screens.order

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted

@Composable
fun OrderConfirmationScreen(
    orderId: Int,
    onDone: () -> Unit,
    onTrackOrder: (Int) -> Unit,
) {
    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(
            modifier = Modifier.fillMaxSize().padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            Icon(
                Icons.Filled.CheckCircle,
                contentDescription = null,
                tint = Color(0xFF16A34A),
                modifier = Modifier.height(88.dp),
            )
            Spacer(modifier = Modifier.height(20.dp))
            Text("Order Placed!", color = Ink, fontSize = 24.sp, fontWeight = FontWeight.Black)
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                if (orderId > 0) "Your order #$orderId has been confirmed." else "Your order has been confirmed.",
                color = Muted,
                fontSize = 14.sp,
            )
            Text(
                "Pay on delivery — cash or UPI at your door.",
                color = Muted,
                fontSize = 13.sp,
            )
            Spacer(modifier = Modifier.height(32.dp))
            Button(onClick = { onTrackOrder(orderId) }, modifier = Modifier.fillMaxWidth()) {
                Text("Track Order", fontWeight = FontWeight.Black)
            }
            Spacer(modifier = Modifier.height(12.dp))
            Button(onClick = onDone, modifier = Modifier.fillMaxWidth()) {
                Text("Back to Home", fontWeight = FontWeight.Black)
            }
        }
    }
}
