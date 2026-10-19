package com.renjo.app.ui.screens.cart

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
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
import com.renjo.app.data.cart.CartLineItem
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted

@Composable
fun CartScreen(
    viewModel: CartViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onCheckout: () -> Unit,
) {
    val cart by viewModel.cart.collectAsState()

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(modifier = Modifier.fillMaxSize()) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                }
                Text("Your Cart", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }

            val currentCart = cart
            if (currentCart == null || currentCart.items.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("Your cart is empty", color = Muted)
                }
            } else {
                Text(currentCart.restaurantName, color = Muted, fontSize = 13.sp, modifier = Modifier.padding(horizontal = 16.dp))
                LazyColumn(modifier = Modifier.weight(1f), contentPadding = PaddingValues(16.dp)) {
                    items(currentCart.items, key = { it.menuItemId.toString() + it.selectedAddOns.hashCode() }) { line ->
                        CartLineRow(
                            line = line,
                            onIncrement = { viewModel.setQuantity(line.menuItemId, line.selectedAddOns, line.quantity + 1) },
                            onDecrement = { viewModel.setQuantity(line.menuItemId, line.selectedAddOns, line.quantity - 1) },
                        )
                    }
                }

                Column(modifier = Modifier.fillMaxWidth().background(Color.White).padding(16.dp)) {
                    Row(modifier = Modifier.fillMaxWidth()) {
                        Text("Subtotal", color = Muted, fontSize = 14.sp, modifier = Modifier.weight(1f))
                        Text("₹${"%.0f".format(currentCart.subtotal)}", color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    }
                    Text(
                        "Delivery fee, taxes, and discounts are calculated at checkout.",
                        color = Muted,
                        fontSize = 11.sp,
                        modifier = Modifier.padding(top = 4.dp, bottom = 12.dp),
                    )
                    Button(onClick = onCheckout, modifier = Modifier.fillMaxWidth()) {
                        Text("Proceed to Checkout", fontWeight = FontWeight.Black)
                    }
                }
            }
        }
    }
}

@Composable
private fun CartLineRow(line: CartLineItem, onIncrement: () -> Unit, onDecrement: () -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth().padding(vertical = 10.dp)) {
        Column(modifier = Modifier.weight(1f)) {
            Text(line.name, color = Ink, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
            if (line.selectedAddOns.isNotEmpty()) {
                Text(line.selectedAddOns.joinToString(", ") { it.name }, color = Muted, fontSize = 11.sp)
            }
            Text("₹${"%.0f".format(line.lineTotal)}", color = Muted, fontSize = 12.sp)
        }
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.background(Color.White, RoundedCornerShape(8.dp)).border(1.dp, Line, RoundedCornerShape(8.dp)),
        ) {
            IconButton(onClick = onDecrement) {
                Icon(Icons.Filled.Remove, contentDescription = "Remove", tint = MaterialTheme.colorScheme.primary)
            }
            Text("${line.quantity}", fontWeight = FontWeight.Bold, color = Ink)
            IconButton(onClick = onIncrement) {
                Icon(Icons.Filled.Add, contentDescription = "Add", tint = MaterialTheme.colorScheme.primary)
            }
        }
    }
}

