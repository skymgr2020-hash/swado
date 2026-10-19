package com.renjo.app.ui.screens.orders

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.IntrinsicSize
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.ReceiptLong
import androidx.compose.material.icons.filled.AccessTime
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.DeliveryDining
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocalMall
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.SupportAgent
import androidx.compose.material3.BottomSheetScaffold
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.rememberBottomSheetScaffoldState
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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.OrderSummaryListItemDto
import com.renjo.app.data.network.dto.OrderTrackDto
import com.renjo.app.ui.theme.CardBorder
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted
import com.google.android.gms.maps.CameraUpdateFactory
import com.google.android.gms.maps.model.CameraPosition
import com.google.android.gms.maps.model.LatLng
import com.google.android.gms.maps.model.LatLngBounds
import com.google.maps.android.compose.GoogleMap
import com.google.maps.android.compose.MapProperties
import com.google.maps.android.compose.MapUiSettings
import com.google.maps.android.compose.Marker
import com.google.maps.android.compose.MarkerState
import com.google.maps.android.compose.Polyline
import com.google.maps.android.compose.rememberCameraPositionState

private val deliverySteps = listOf(
    TrackStep("Order Placed", Icons.Filled.Storefront, setOf("pending", "confirmed")),
    TrackStep("Preparing", Icons.Filled.Restaurant, setOf("preparing", "ready_for_pickup", "reached_pickup")),
    TrackStep("On The Way", Icons.Filled.DeliveryDining, setOf("picked_up", "on_the_way")),
    TrackStep("Delivered", Icons.Filled.Home, setOf("delivered")),
)
private val takeawaySteps = listOf(
    TrackStep("Order Placed", Icons.Filled.Storefront, setOf("pending")),
    TrackStep("Confirmed", Icons.Filled.Storefront, setOf("confirmed")),
    TrackStep("Preparing", Icons.Filled.Restaurant, setOf("preparing")),
    TrackStep("Ready to Collect", Icons.Filled.LocalMall, setOf("ready_for_pickup")),
    TrackStep("Collected", Icons.Filled.Home, setOf("delivered")),
)
private val deliveryStatusIndex = mapOf(
    "pending" to 0, "confirmed" to 0,
    "preparing" to 1, "ready_for_pickup" to 1, "reached_pickup" to 1,
    "picked_up" to 2, "on_the_way" to 2,
    "delivered" to 3,
)
private val takeawayStatusIndex = mapOf(
    "pending" to 0,
    "confirmed" to 1,
    "preparing" to 2,
    "ready_for_pickup" to 3,
    "delivered" to 4,
)

private data class TrackStep(val label: String, val icon: ImageVector, val statuses: Set<String>)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrderDetailScreen(
    viewModel: OrderDetailViewModel = hiltViewModel(),
    onBack: () -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    var cancelReason by remember { mutableStateOf("") }
    var showCancelForm by remember { mutableStateOf(false) }

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        when (val state = uiState) {
            is OrderDetailUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            is OrderDetailUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(24.dp)) {
                    Text("Couldn't load order", color = Danger, fontWeight = FontWeight.Black, fontSize = 18.sp)
                    Text(state.message, color = Muted, fontSize = 12.sp, modifier = Modifier.padding(top = 6.dp))
                }
            }
            is OrderDetailUiState.Loaded -> {
                val order = state.order
                val track = state.track
                val cancelled = order.status == "cancelled"
                val scaffoldState = rememberBottomSheetScaffoldState()
                BottomSheetScaffold(
                    scaffoldState = scaffoldState,
                    sheetPeekHeight = 160.dp,
                    sheetShape = RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp),
                    sheetContainerColor = Color(0xFFF7F7F7),
                    sheetShadowElevation = 20.dp,
                    sheetDragHandle = {
                        Box(
                            modifier = Modifier
                                .padding(vertical = 10.dp)
                                .width(42.dp)
                                .height(5.dp)
                                .background(Color(0xFFDCE1EA), RoundedCornerShape(999.dp)),
                        )
                    },
                    sheetContent = {
                        OrderSummaryLink(order)
                        LazyColumn(contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 28.dp)) {
                            item {
                                if (cancelled) {
                                    CancelledCard(order)
                                } else {
                                    StatusTimelineCard(orderType = order.orderType, status = order.status)
                                    Spacer(modifier = Modifier.height(14.dp))
                                    CancellationWindowCard(order)
                                    Spacer(modifier = Modifier.height(14.dp))
                                    PaymentStatusCard(order)
                                }
                                Spacer(modifier = Modifier.height(14.dp))
                                PremiumOrderSummaryCard(order)
                                Spacer(modifier = Modifier.height(14.dp))
                                OtpCard(order)

                                if (order.status in setOf("pending", "confirmed")) {
                                    Spacer(modifier = Modifier.height(14.dp))
                                    ActionCard {
                                        if (!showCancelForm) {
                                            OutlinedButton(onClick = { showCancelForm = true }, modifier = Modifier.fillMaxWidth()) {
                                                Text("Cancel Order")
                                            }
                                        } else {
                                            OutlinedTextField(
                                                value = cancelReason,
                                                onValueChange = { cancelReason = it },
                                                label = { Text("Reason for cancellation") },
                                                modifier = Modifier.fillMaxWidth(),
                                            )
                                            Button(
                                                onClick = { viewModel.cancelOrder(cancelReason.ifBlank { "Cancelled by customer" }) },
                                                modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                                            ) { Text("Confirm Cancellation") }
                                        }
                                    }
                                }

                                if (order.status == "delivered") {
                                    Spacer(modifier = Modifier.height(14.dp))
                                    FeedbackCard(
                                        onSubmit = { viewModel.submitFeedback(5, null) },
                                        onTip = { viewModel.tip(20.0) },
                                    )
                                }

                                actionMessage?.let {
                                    Text(it, color = Muted, fontSize = 12.sp, modifier = Modifier.padding(top = 12.dp, bottom = 8.dp))
                                }
                            }
                        }
                    },
                ) { innerPadding ->
                    Box(modifier = Modifier.fillMaxSize().padding(innerPadding).background(Color(0xFFF7F7F7))) {
                        if (cancelled) {
                            Box(modifier = Modifier.fillMaxSize().background(Danger))
                        } else {
                            TrackingMapCard(order = order, track = track)
                        }

                        Box(modifier = Modifier.align(Alignment.TopCenter).padding(start = 12.dp, top = 8.dp, end = 12.dp)) {
                            TrackingHeader(onBack = onBack, order = order, track = track, cancelled = cancelled)
                        }
                    }
                }
            }
        }
    }

    LaunchedEffect(actionMessage) {
        if (actionMessage != null) {
            kotlinx.coroutines.delay(3000)
            viewModel.dismissActionMessage()
        }
    }
}

@Composable
private fun TrackingHeader(onBack: () -> Unit, order: OrderSummaryListItemDto, track: OrderTrackDto?, cancelled: Boolean) {
    val primary = if (cancelled) Danger else MaterialTheme.colorScheme.primary
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(elevation = 18.dp, shape = RoundedCornerShape(20.dp), ambientColor = Color.Black.copy(alpha = 0.20f))
            .background(primary, RoundedCornerShape(20.dp))
            .padding(start = 10.dp, top = 8.dp, end = 10.dp, bottom = 12.dp),
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack, modifier = Modifier.size(32.dp)) {
                Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color.White)
            }
            Spacer(modifier = Modifier.width(6.dp))
            Text(
                order.restaurantName ?: "Order Tracking",
                color = Color.White,
                fontSize = 13.5.sp,
                fontWeight = FontWeight.ExtraBold,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier.weight(1f),
            )
            Icon(Icons.Filled.SupportAgent, contentDescription = null, tint = Color.White, modifier = Modifier.size(22.dp))
        }
        Spacer(modifier = Modifier.height(8.dp))
        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(start = 6.dp)) {
            Text(
                track?.statusText ?: statusTitle(order.status),
                color = Color.White,
                fontSize = 16.sp,
                fontWeight = FontWeight.Black,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier.weight(1f),
            )
            if (!cancelled) {
                Spacer(modifier = Modifier.width(8.dp))
                HeaderChip(Icons.Filled.AccessTime, if (order.status == "delivered") "Delivered" else "Arriving in ${track?.estimatedDeliveryLabel ?: order.estimatedDeliveryLabel ?: "--"}")
            }
        }
    }
}
@Composable
private fun HeaderChip(icon: ImageVector, label: String) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier.background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(999.dp)).padding(horizontal = 10.dp, vertical = 6.dp),
    ) {
        Icon(icon, contentDescription = null, tint = Color.White, modifier = Modifier.size(13.dp))
        Spacer(modifier = Modifier.width(5.dp))
        Text(label, color = Color.White, fontSize = 11.5.sp, fontWeight = FontWeight.Black)
    }
}

@Composable
private fun TrackingMapCard(order: OrderSummaryListItemDto, track: OrderTrackDto?) {
    val pickup = latLngOrNull(track?.pickupLocation?.latitude, track?.pickupLocation?.longitude)
    val delivery = latLngOrNull(track?.order?.deliveryLat, track?.order?.deliveryLng)
    val driver = latLngOrNull(track?.driverLocation?.lat, track?.driverLocation?.lng)
    val markerPoints = listOfNotNull(driver, pickup, delivery).distinct()
    val routePoints = listOfNotNull(driver ?: pickup, delivery ?: pickup).distinct()

    if (markerPoints.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Brush.linearGradient(listOf(Color(0xFFEAF7EF), Color(0xFFFFF2E8), Color.White))),
        ) {
            RouteFallback(order = order, track = track)
        }
        return
    }

    val cameraPositionState = rememberCameraPositionState {
        position = CameraPosition.fromLatLngZoom(markerPoints.first(), if (markerPoints.size == 1) 15f else 13f)
    }

    LaunchedEffect(markerPoints) {
        if (markerPoints.size == 1) {
            cameraPositionState.animate(CameraUpdateFactory.newLatLngZoom(markerPoints.first(), 15f))
        } else {
            val boundsBuilder = LatLngBounds.builder()
            markerPoints.forEach(boundsBuilder::include)
            cameraPositionState.animate(CameraUpdateFactory.newLatLngBounds(boundsBuilder.build(), 140))
        }
    }

    Box(modifier = Modifier.fillMaxSize()) {
        GoogleMap(
            modifier = Modifier.fillMaxSize(),
            cameraPositionState = cameraPositionState,
            properties = MapProperties(isMyLocationEnabled = false),
            uiSettings = MapUiSettings(
                compassEnabled = false,
                mapToolbarEnabled = false,
                myLocationButtonEnabled = false,
                zoomControlsEnabled = false,
            ),
        ) {
            pickup?.let {
                Marker(
                    state = MarkerState(position = it),
                    title = track?.pickupLocation?.name ?: order.restaurantName ?: "Restaurant",
                )
            }
            delivery?.let {
                Marker(
                    state = MarkerState(position = it),
                    title = if (order.orderType == "takeaway") "Pickup" else "Delivery location",
                )
            }
            driver?.let {
                Marker(state = MarkerState(position = it), title = "Delivery partner")
            }
            if (routePoints.size >= 2) {
                Polyline(points = routePoints, color = MaterialTheme.colorScheme.primary, width = 8f)
            }
        }
        Row(
            modifier = Modifier
                .align(Alignment.CenterStart)
                .padding(start = 14.dp)
                .background(Color.White.copy(alpha = 0.96f), RoundedCornerShape(14.dp))
                .padding(horizontal = 12.dp, vertical = 9.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(modifier = Modifier.size(9.dp).background(MaterialTheme.colorScheme.primary, CircleShape))
            Spacer(modifier = Modifier.width(8.dp))
            Text(statusTitle(order.status), color = Color(0xFF222222), fontSize = 12.sp, fontWeight = FontWeight.ExtraBold, maxLines = 1)
        }
        Box(
            modifier = Modifier
                .align(Alignment.CenterEnd)
                .padding(end = 14.dp)
                .size(42.dp)
                .background(Color.White, CircleShape),
            contentAlignment = Alignment.Center,
        ) {
            Icon(Icons.Filled.Refresh, contentDescription = null, tint = Muted, modifier = Modifier.size(21.dp))
        }
    }
}
@Composable
private fun RouteFallback(order: OrderSummaryListItemDto, track: OrderTrackDto?) {
    Column(modifier = Modifier.fillMaxSize().padding(22.dp), verticalArrangement = Arrangement.Center) {
        RoutePoint(Icons.Filled.Restaurant, MaterialTheme.colorScheme.primary, order.restaurantName ?: "Restaurant")
        Box(modifier = Modifier.padding(start = 15.dp).width(2.dp).height(46.dp).background(MaterialTheme.colorScheme.primary.copy(alpha = 0.35f)))
        RoutePoint(
            Icons.Filled.Home,
            Color(0xFF16A34A),
            if (order.orderType == "takeaway") "Pickup at store" else track?.estimatedDeliveryLabel ?: "Delivery location",
        )
    }
}

@Composable
private fun RoutePoint(icon: ImageVector, color: Color, label: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Box(modifier = Modifier.size(32.dp).background(color, CircleShape), contentAlignment = Alignment.Center) {
            Icon(icon, contentDescription = null, tint = Color.White, modifier = Modifier.size(17.dp))
        }
        Spacer(modifier = Modifier.width(10.dp))
        Text(label, color = Ink, fontSize = 13.sp, fontWeight = FontWeight.Black, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

@Composable
private fun StatusTimelineCard(orderType: String?, status: String?) {
    val isTakeaway = orderType == "takeaway"
    val steps = if (isTakeaway) takeawaySteps else deliverySteps
    val currentIndex = if (isTakeaway) takeawayStatusIndex[status] ?: 0 else deliveryStatusIndex[status] ?: 0
    val primary = MaterialTheme.colorScheme.primary

    CardShell {
        Text("ORDER STATUS", color = Color(0xFF666666), fontSize = 11.5.sp, fontWeight = FontWeight.Bold, letterSpacing = 0.8.sp)
        Spacer(modifier = Modifier.height(12.dp))
        steps.forEachIndexed { index, step ->
            val done = index < currentIndex
            val current = index == currentIndex
            Row(modifier = Modifier.fillMaxWidth().height(IntrinsicSize.Min)) {
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.width(30.dp)) {
                    Box(
                        modifier = Modifier
                            .size(if (current) 24.dp else 20.dp)
                            .background(if (done) primary else Color.White, CircleShape)
                            .border(if (done) 0.dp else 2.dp, if (current || done) primary else Color(0xFFD8D8D8), CircleShape),
                        contentAlignment = Alignment.Center,
                    ) {
                        when {
                            done -> Icon(Icons.Filled.Check, contentDescription = null, tint = Color.White, modifier = Modifier.size(12.dp))
                            current -> Box(modifier = Modifier.size(8.dp).background(primary, CircleShape))
                            else -> Icon(step.icon, contentDescription = null, tint = Color(0xFFB5B5BE), modifier = Modifier.size(12.dp))
                        }
                    }
                    if (index != steps.lastIndex) {
                        Box(modifier = Modifier.width(2.dp).fillMaxHeight().background(if (index < currentIndex) primary else Color(0xFFE4E4E4)))
                    }
                }
                Column(modifier = Modifier.padding(start = 10.dp, bottom = 16.dp)) {
                    Text(
                        step.label,
                        color = when {
                            done -> Ink
                            current -> primary
                            else -> Color(0xFF9A9A9A)
                        },
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Black,
                    )
                    Text(
                        when {
                            current -> "In progress"
                            done -> "Completed"
                            else -> "Pending"
                        },
                        color = if (current) primary else Color(0xFF888888),
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }
        }
    }
}


@Composable
private fun OrderSummaryLink(order: OrderSummaryListItemDto) {
    if (order.status == "cancelled") return
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier.fillMaxWidth().background(Color.White).padding(start = 16.dp, top = 12.dp, end = 16.dp, bottom = 12.dp),
    ) {
        Text(
            "Order #${order.orderNumber ?: order.id} - View order summary",
            color = Color(0xFF16A34A),
            fontSize = 13.sp,
            fontWeight = FontWeight.Bold,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
            modifier = Modifier.weight(1f),
        )
        Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = Color(0xFF16A34A), modifier = Modifier.size(20.dp))
    }
}

@Composable
private fun CancellationWindowCard(order: OrderSummaryListItemDto) {
    if (order.status !in setOf("pending", "confirmed")) return
    CardShell {
        SectionTitle(Icons.Filled.AccessTime, "Cancellation Window")
        Spacer(modifier = Modifier.height(8.dp))
        Text("You can cancel only before the restaurant accepts the order.", color = Muted, fontSize = 12.5.sp, fontWeight = FontWeight.Medium)
    }
}@Composable
private fun PaymentStatusCard(order: OrderSummaryListItemDto) {
    CardShell {
        SectionTitle(Icons.Filled.Payments, "Payment Status")
        Spacer(modifier = Modifier.height(10.dp))
        DetailRow("Payment method", order.paymentMethod?.replace("_", " ") ?: "Not selected")
        DetailRow("Payment status", order.paymentStatus?.replace("_", " ") ?: "Pending")
    }
}

@Composable
private fun OtpCard(order: OrderSummaryListItemDto) {
    CardShell {
        SectionTitle(Icons.AutoMirrored.Filled.ReceiptLong, if (order.orderType == "takeaway") "Pickup OTP" else "Delivery OTP")
        Spacer(modifier = Modifier.height(10.dp))
        Text(
            order.deliveryOtp ?: if (order.orderType == "takeaway") "Pickup OTP will appear when ready" else "Delivery OTP will appear when the driver is nearby",
            color = if (order.deliveryOtp == null) Muted else Ink,
            fontSize = if (order.deliveryOtp == null) 12.5.sp else 24.sp,
            fontWeight = if (order.deliveryOtp == null) FontWeight.SemiBold else FontWeight.Black,
        )
    }
}

@Composable
private fun PremiumOrderSummaryCard(order: OrderSummaryListItemDto) {
    CardShell {
        SectionTitle(Icons.AutoMirrored.Filled.ReceiptLong, "Order Summary")
        Spacer(modifier = Modifier.height(10.dp))
        Text(order.restaurantName ?: "Restaurant", color = Ink, fontSize = 15.sp, fontWeight = FontWeight.Black, maxLines = 1)
        Text("Order ID: #${order.orderNumber ?: order.id}", color = Muted, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 4.dp))
        Spacer(modifier = Modifier.height(12.dp))
        order.subtotal?.let { DetailRow("Subtotal", money(it)) }
        order.deliveryFee?.let { DetailRow("Delivery fee", money(it)) }
        order.platformFee?.let { DetailRow("Platform fee", money(it)) }
        order.tax?.let { DetailRow("Tax", money(it)) }
        order.discount?.takeIf { it > 0 }?.let { DetailRow("Discount", "- ${money(it)}", valueColor = Color(0xFF16A34A)) }
        Box(modifier = Modifier.fillMaxWidth().height(1.dp).background(Line).padding(top = 4.dp))
        DetailRow("Total", money(order.total ?: 0.0), bold = true)
    }
}

@Composable
private fun CancelledCard(order: OrderSummaryListItemDto) {
    CardShell {
        SectionTitle(Icons.Filled.Close, "Order Cancelled", color = Danger)
        Spacer(modifier = Modifier.height(8.dp))
        Text("This order is no longer active.", color = Muted, fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold)
        Spacer(modifier = Modifier.height(10.dp))
        DetailRow("Order ID", "#${order.orderNumber ?: order.id}")
        DetailRow("Total", money(order.total ?: 0.0), bold = true)
    }
}

@Composable
private fun FeedbackCard(onSubmit: () -> Unit, onTip: () -> Unit) {
    CardShell {
        Text("Rate your order", color = Ink, fontWeight = FontWeight.Black, fontSize = 14.sp)
        Row(modifier = Modifier.padding(top = 8.dp)) {
            repeat(5) {
                Icon(Icons.Filled.Star, contentDescription = null, tint = Color(0xFFF59E0B), modifier = Modifier.size(26.dp).padding(end = 4.dp))
            }
        }
        Button(onClick = onSubmit, modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) { Text("Submit 5-star rating") }
        OutlinedButton(onClick = onTip, modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) { Text("Tip your driver Rs 20") }
    }
}

@Composable
private fun ActionCard(content: @Composable ColumnScope.() -> Unit) {
    CardShell(content = content)
}

@Composable
private fun CardShell(content: @Composable ColumnScope.() -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(elevation = 8.dp, shape = RoundedCornerShape(18.dp), ambientColor = Color.Black.copy(alpha = 0.05f))
            .background(Color.White, RoundedCornerShape(18.dp))
            .border(1.dp, CardBorder, RoundedCornerShape(18.dp))
            .padding(16.dp),
        content = content,
    )
}

@Composable
private fun SectionTitle(icon: ImageVector, title: String, color: Color = MaterialTheme.colorScheme.primary) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Box(modifier = Modifier.size(34.dp).background(color.copy(alpha = 0.10f), RoundedCornerShape(12.dp)), contentAlignment = Alignment.Center) {
            Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(18.dp))
        }
        Spacer(modifier = Modifier.width(10.dp))
        Text(title, color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Black)
    }
}

@Composable
private fun DetailRow(label: String, value: String, bold: Boolean = false, valueColor: Color = Ink) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 5.dp), verticalAlignment = Alignment.CenterVertically) {
        Text(label, color = Muted, fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.weight(1f))
        Text(value, color = valueColor, fontSize = if (bold) 15.sp else 13.sp, fontWeight = if (bold) FontWeight.Black else FontWeight.Bold)
    }
}

private fun statusTitle(status: String?): String = status?.replace("_", " ")?.replaceFirstChar { it.uppercase() } ?: "Order confirmed"
private fun money(value: Double): String = "Rs ${"%.0f".format(value)}"
private fun latLngOrNull(lat: Double?, lng: Double?): LatLng? {
    if (lat == null || lng == null) return null
    if (lat !in -90.0..90.0 || lng !in -180.0..180.0) return null
    return LatLng(lat, lng)
}