package com.renjo.app.ui.screens.checkout

import android.app.Activity
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForwardIos
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.KeyboardArrowUp
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
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
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.AddressDto
import com.renjo.app.data.network.dto.PaymentSessionDto
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.CardBorder
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted
import com.renjo.app.ui.theme.SuccessDark
import com.stripe.android.PaymentConfiguration
import com.stripe.android.paymentsheet.PaymentSheet
import com.stripe.android.paymentsheet.PaymentSheetResult
import com.stripe.android.paymentsheet.rememberPaymentSheet

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CheckoutScreen(
    viewModel: CheckoutViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onAddAddress: () -> Unit,
    onOrderPlaced: (Int) -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    val placeOrderState by viewModel.placeOrderState.collectAsState()
    val selectedPaymentMethod by viewModel.selectedPaymentMethod.collectAsState()
    var showPaymentSheet by remember { mutableStateOf(false) }
    val paymentSheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)

    val context = LocalContext.current
    val activity = context as? Activity
    var pendingStripe by remember { mutableStateOf<Pair<Int, PaymentSessionDto>?>(null) }
    val stripePaymentSheet = rememberPaymentSheet { result ->
        val pending = pendingStripe
        if (pending != null) {
            val (orderId, session) = pending
            when (result) {
                is PaymentSheetResult.Completed -> {
                    val intentId = session.clientSecret?.substringBefore("_secret_")
                    if (intentId != null) viewModel.verifyStripePayment(orderId, intentId) else viewModel.onStripeCancelledOrFailed(orderId)
                }
                else -> viewModel.onStripeCancelledOrFailed(orderId)
            }
        }
    }

    LaunchedEffect(placeOrderState) {
        when (val state = placeOrderState) {
            is PlaceOrderState.Success -> onOrderPlaced(state.orderId)
            is PlaceOrderState.RequiresPayment -> {
                val session = state.session
                if (session.gateway == "stripe") {
                    val publishableKey = session.publishableKey
                    val clientSecret = session.clientSecret
                    if (publishableKey != null && clientSecret != null) {
                        PaymentConfiguration.init(context, publishableKey)
                        pendingStripe = state.orderId to session
                        stripePaymentSheet.presentWithPaymentIntent(
                            clientSecret,
                            PaymentSheet.Configuration(merchantDisplayName = "Swado"),
                        )
                    } else {
                        viewModel.onStripeCancelledOrFailed(state.orderId)
                    }
                } else if (activity != null) {
                    viewModel.launchGateway(activity, state.orderId, session)
                }
            }
            else -> {}
        }
    }

    Surface(modifier = Modifier.fillMaxSize(), color = Color(0xFFF5F6FB)) {
        Column(modifier = Modifier.fillMaxSize()) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Ink) }
                Text("Checkout", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }

            when (val state = uiState) {
                is CheckoutUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                is CheckoutUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Danger)
                }
                is CheckoutUiState.NeedsAddress -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Add a delivery address to continue", color = Muted)
                        Button(onClick = onAddAddress, modifier = Modifier.padding(top = 16.dp)) { Text("Add Address") }
                    }
                }
                is CheckoutUiState.Ready -> {
                    LazyColumn(modifier = Modifier.weight(1f), contentPadding = PaddingValues(16.dp)) {
                        item {
                            Text("Deliver to", color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Black, modifier = Modifier.padding(bottom = 8.dp))
                        }
                        items(state.addresses, key = { it.id }) { address ->
                            AddressRow(address, address.id == state.selectedAddress.id) { viewModel.selectAddress(address) }
                        }
                        item {
                            OutlinedButton(onClick = onAddAddress, modifier = Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 16.dp)) {
                                Icon(Icons.Filled.Add, contentDescription = null, modifier = Modifier.height(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Add new address")
                            }
                        }
                        item {
                            Column(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .background(Color.White, RoundedCornerShape(18.dp))
                                    .border(1.dp, CardBorder, RoundedCornerShape(18.dp))
                                    .padding(14.dp),
                            ) {
                                Text("Bill Details", color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Black, modifier = Modifier.padding(bottom = 8.dp))
                                if (state.summaryLoading) {
                                    CircularProgressIndicator(modifier = Modifier.height(20.dp))
                                } else if (state.summary != null) {
                                    BillRow("Item total", state.summary.subtotal)
                                    BillRow("Delivery fee", state.summary.deliveryFee)
                                    BillRow("Platform fee", state.summary.platformFee)
                                    BillRow("Taxes & charges", state.summary.tax)
                                    if (state.summary.discount > 0) BillRow("Discount", -state.summary.discount, highlight = true)
                                    Spacer(modifier = Modifier.height(4.dp))
                                    androidx.compose.material3.HorizontalDivider(color = CardBorder, modifier = Modifier.padding(vertical = 8.dp))
                                    Row(modifier = Modifier.fillMaxWidth()) {
                                        Text("To Pay", color = Ink, fontSize = 16.sp, fontWeight = FontWeight.Black, modifier = Modifier.weight(1f))
                                        Text("₹${"%.0f".format(state.summary.total)}", color = Ink, fontSize = 16.sp, fontWeight = FontWeight.Black)
                                    }
                                    state.summary.eta?.etaRange?.let {
                                        Text("Estimated delivery: $it", color = Muted, fontSize = 12.sp, modifier = Modifier.padding(top = 6.dp))
                                    }
                                } else {
                                    Text("Couldn't calculate pricing — check your connection and try again.", color = Danger, fontSize = 12.sp)
                                }
                            }
                        }
                        if (placeOrderState is PlaceOrderState.Error) {
                            item {
                                Text((placeOrderState as PlaceOrderState.Error).message, color = Danger, fontSize = 12.sp, modifier = Modifier.padding(top = 12.dp))
                            }
                        }
                    }

                    val processing = placeOrderState is PlaceOrderState.Placing ||
                        placeOrderState is PlaceOrderState.RequiresPayment ||
                        placeOrderState is PlaceOrderState.VerifyingPayment

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .shadow(elevation = 16.dp, shape = RoundedCornerShape(topStart = 28.dp, topEnd = 28.dp))
                            .background(Color.White, RoundedCornerShape(topStart = 28.dp, topEnd = 28.dp))
                            .padding(14.dp),
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .weight(1f)
                                .clip(RoundedCornerShape(12.dp))
                                .clickable { showPaymentSheet = true }
                                .padding(vertical = 6.dp, horizontal = 4.dp),
                        ) {
                            Box(
                                modifier = Modifier.size(34.dp).background(Color(0xFFF3F7F4), RoundedCornerShape(10.dp)),
                                contentAlignment = Alignment.Center,
                            ) {
                                Icon(Icons.Filled.Payments, contentDescription = null, tint = Muted, modifier = Modifier.size(18.dp))
                            }
                            Spacer(modifier = Modifier.width(8.dp))
                            Column {
                                Text("PAY USING", color = Muted, fontSize = 10.sp, fontWeight = FontWeight.Black)
                                Text(viewModel.paymentMethodTitle(), color = Ink, fontSize = 13.sp, fontWeight = FontWeight.Black, maxLines = 1)
                            }
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(Icons.Filled.KeyboardArrowUp, contentDescription = null, tint = Muted, modifier = Modifier.size(16.dp))
                        }

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .weight(1.4f)
                                .height(54.dp)
                                .shadow(elevation = 10.dp, shape = RoundedCornerShape(18.dp))
                                .background(
                                    Brush.linearGradient(
                                        colors = listOf(androidx.compose.material3.MaterialTheme.colorScheme.primary, androidx.compose.material3.MaterialTheme.colorScheme.secondary),
                                        start = Offset(0f, 0f),
                                    ),
                                    RoundedCornerShape(18.dp),
                                )
                                .clickable(enabled = !processing && state.summary != null) { viewModel.placeOrder(null) }
                                .padding(horizontal = 14.dp),
                        ) {
                            if (processing) {
                                CircularProgressIndicator(modifier = Modifier.height(20.dp), color = Color.White)
                            } else {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        "₹${"%.0f".format(state.summary?.total ?: 0.0)}",
                                        color = Color.White,
                                        fontSize = 15.sp,
                                        fontWeight = FontWeight.Black,
                                    )
                                    Text("TOTAL", color = Color.White.copy(alpha = 0.85f), fontSize = 10.sp, fontWeight = FontWeight.Bold)
                                }
                                Text("Place Order", color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Black)
                                Spacer(modifier = Modifier.width(6.dp))
                                Icon(Icons.AutoMirrored.Filled.ArrowForwardIos, contentDescription = null, tint = Color.White, modifier = Modifier.size(14.dp))
                            }
                        }
                    }
                }
            }
        }
    }

    if (showPaymentSheet) {
        PaymentMethodSheet(
            sheetState = paymentSheetState,
            options = viewModel.paymentOptions,
            selectedKey = selectedPaymentMethod,
            onSelect = { key ->
                viewModel.selectPaymentMethod(key)
                showPaymentSheet = false
            },
            onDismiss = { showPaymentSheet = false },
        )
    }
}

@Composable
private fun AddressRow(address: AddressDto, selected: Boolean, onSelect: () -> Unit) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp)
            .border(if (selected) 1.5.dp else 1.dp, if (selected) androidx.compose.material3.MaterialTheme.colorScheme.primary else CardBorder, RoundedCornerShape(18.dp))
            .background(Color.White, RoundedCornerShape(18.dp)),
    ) {
        RadioButton(selected = selected, onClick = onSelect)
        Column(modifier = Modifier.weight(1f).padding(vertical = 10.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Filled.LocationOn, contentDescription = null, tint = Muted, modifier = Modifier.height(14.dp))
                Spacer(modifier = Modifier.width(4.dp))
                Text(address.name ?: "Address", color = Ink, fontWeight = FontWeight.Black, fontSize = 13.sp)
            }
            Text(address.address, color = Muted, fontSize = 12.sp, maxLines = 2, lineHeight = 15.sp)
        }
    }
}

@Composable
private fun BillRow(label: String, amount: Double, highlight: Boolean = false) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 3.dp)) {
        Text(label, color = Muted, fontSize = 13.sp, fontWeight = FontWeight.Medium, modifier = Modifier.weight(1f))
        Text(
            "${if (amount < 0) "-" else ""}₹${"%.0f".format(kotlin.math.abs(amount))}",
            color = if (highlight) SuccessDark else Ink,
            fontSize = 13.sp,
            fontWeight = FontWeight.SemiBold,
        )
    }
}
