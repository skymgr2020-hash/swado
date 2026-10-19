package com.renjo.app.ui.screens.orders

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.AccessTime
import androidx.compose.material.icons.filled.Cancel
import androidx.compose.material.icons.filled.CheckCircleOutline
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.DeliveryDining
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.RestaurantMenu
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.SupportAgent
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.OrderSummaryListItemDto
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Faint
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted
import com.renjo.app.ui.theme.StatusCancelledBg
import com.renjo.app.ui.theme.StatusCancelledFg
import com.renjo.app.ui.theme.StatusConfirmedBg
import com.renjo.app.ui.theme.StatusConfirmedFg
import com.renjo.app.ui.theme.StatusDeliveredBg
import com.renjo.app.ui.theme.StatusDeliveredFg
import com.renjo.app.ui.theme.StatusLiveBg
import com.renjo.app.ui.theme.StatusLiveFg

private data class StatusStyle(val label: String, val fg: Color, val bg: Color, val icon: ImageVector)

private fun statusStyle(status: String?): StatusStyle = when (status) {
    "cancelled" -> StatusStyle("CANCELLED", StatusCancelledFg, StatusCancelledBg, Icons.Filled.Cancel)
    "delivered" -> StatusStyle("DELIVERED", StatusDeliveredFg, StatusDeliveredBg, Icons.Filled.CheckCircleOutline)
    "on_the_way", "picked_up" -> StatusStyle("LIVE", StatusLiveFg, StatusLiveBg, Icons.Filled.AccessTime)
    else -> StatusStyle("CONFIRMED", StatusConfirmedFg, StatusConfirmedBg, Icons.Filled.AccessTime)
}

private fun isDelivered(order: OrderSummaryListItemDto) = order.status == "delivered"
private fun isCancelled(order: OrderSummaryListItemDto) = order.status == "cancelled"
private fun isOngoing(order: OrderSummaryListItemDto) = !isDelivered(order) && !isCancelled(order)

@Composable
fun OrdersTabScreen(
    viewModel: OrdersTabViewModel = hiltViewModel(),
    onOpenOrder: (Int) -> Unit,
    onBackHome: () -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsState()
    var selectedTab by remember { mutableIntStateOf(0) }
    var query by remember { mutableStateOf("") }

    Surface(modifier = Modifier.fillMaxSize(), color = Color.White) {
        Column(modifier = Modifier.fillMaxSize()) {
            OrdersHeader(onBackHome = onBackHome)
            when (val state = uiState) {
                is OrdersUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                is OrdersUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(24.dp)) {
                        Text("Couldn't load orders", color = Danger, fontWeight = FontWeight.Black, fontSize = 18.sp)
                        Text(state.message, color = Muted, fontSize = 12.sp, modifier = Modifier.padding(top = 6.dp))
                        Button(onClick = viewModel::refresh, modifier = Modifier.padding(top = 16.dp)) { Text("Retry") }
                    }
                }
                is OrdersUiState.Loaded -> {
                    val allOrders = state.orders
                    OrdersTabs(
                        selectedTab = selectedTab,
                        onTabSelected = { selectedTab = it },
                        allCount = allOrders.size,
                        ongoingCount = allOrders.count(::isOngoing),
                        completedCount = allOrders.count(::isDelivered),
                        cancelledCount = allOrders.count(::isCancelled),
                    )
                    SearchRow(query = query, onQueryChange = { query = it })
                    val visibleOrders = remember(allOrders, selectedTab, query) {
                        val tabbed = when (selectedTab) {
                            1 -> allOrders.filter(::isOngoing)
                            2 -> allOrders.filter(::isDelivered)
                            3 -> allOrders.filter(::isCancelled)
                            else -> allOrders
                        }
                        val normalized = query.trim().lowercase()
                        if (normalized.isEmpty()) tabbed else tabbed.filter { order ->
                            listOfNotNull(order.restaurantName, order.orderNumber, order.status)
                                .any { it.lowercase().contains(normalized) }
                        }
                    }
                    if (visibleOrders.isEmpty()) {
                        EmptyOrdersState(selectedTab = selectedTab)
                    } else {
                        LazyColumn(contentPadding = PaddingValues(start = 16.dp, top = 4.dp, end = 16.dp, bottom = 118.dp)) {
                            items(visibleOrders, key = { it.id }) { order ->
                                OrderCard(order = order, onClick = { onOpenOrder(order.id) })
                                Spacer(modifier = Modifier.height(14.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun OrdersHeader(onBackHome: () -> Unit) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier.fillMaxWidth().height(68.dp).padding(start = 16.dp, top = 8.dp, end = 16.dp, bottom = 6.dp),
    ) {
        Box(modifier = Modifier.size(44.dp).background(Color.White, CircleShape).clickable(onClick = onBackHome), contentAlignment = Alignment.Center) {
            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color.Black, modifier = Modifier.size(28.dp))
        }
        Spacer(modifier = Modifier.width(12.dp))
        Text("My Orders", color = Color.Black, fontSize = 20.sp, fontWeight = FontWeight.Black, modifier = Modifier.weight(1f))
        TextButton(onClick = {}) {
            Icon(Icons.Filled.SupportAgent, contentDescription = null, tint = Color.Black, modifier = Modifier.size(23.dp))
            Spacer(modifier = Modifier.width(4.dp))
            Text("Help", color = Color.Black, fontWeight = FontWeight.Bold, fontSize = 14.5.sp)
        }
    }
}

@Composable
private fun OrdersTabs(selectedTab: Int, onTabSelected: (Int) -> Unit, allCount: Int, ongoingCount: Int, completedCount: Int, cancelledCount: Int) {
    Row(modifier = Modifier.fillMaxWidth().height(58.dp).border(width = 0.5.dp, color = Color(0xFFEDEDED))) {
        OrderTab("All Orders", allCount, selectedTab == 0, Modifier.weight(1f)) { onTabSelected(0) }
        OrderTab("Ongoing", ongoingCount, selectedTab == 1, Modifier.weight(1f)) { onTabSelected(1) }
        OrderTab("Completed", completedCount, selectedTab == 2, Modifier.weight(1f)) { onTabSelected(2) }
        OrderTab("Cancelled", cancelledCount, selectedTab == 3, Modifier.weight(1f)) { onTabSelected(3) }
    }
}

@Composable
@Suppress("UNUSED_PARAMETER")
private fun OrderTab(label: String, count: Int, selected: Boolean, modifier: Modifier, onClick: () -> Unit) {
    val primary = MaterialTheme.colorScheme.primary
    Column(modifier = modifier.fillMaxSize().clickable(onClick = onClick), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Bottom) {
        Text(label, maxLines = 1, overflow = TextOverflow.Ellipsis, color = if (selected) primary else Color(0xFF6B6B73), fontSize = 12.5.sp, fontWeight = if (selected) FontWeight.Black else FontWeight.Bold)
        Spacer(modifier = Modifier.height(15.dp))
        Box(modifier = Modifier.height(2.5.dp).width(if (selected) 78.dp else 0.dp).background(primary))
    }
}

@Composable
private fun SearchRow(query: String, onQueryChange: (String) -> Unit) {
    OutlinedTextField(
        value = query,
        onValueChange = onQueryChange,
        singleLine = true,
        placeholder = { Text("Search by restaurant or item", color = Color(0xFF76767D), fontSize = 12.5.sp, fontWeight = FontWeight.Medium) },
        leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null, tint = Color.Black, modifier = Modifier.size(27.dp)) },
        trailingIcon = if (query.isNotEmpty()) {
            { Icon(Icons.Filled.Close, contentDescription = "Clear", tint = Muted, modifier = Modifier.clickable { onQueryChange("") }) }
        } else null,
        shape = RoundedCornerShape(12.dp),
        modifier = Modifier.fillMaxWidth().padding(start = 16.dp, top = 18.dp, end = 16.dp, bottom = 12.dp).height(52.dp),
    )
}

@Composable
private fun EmptyOrdersState(selectedTab: Int) {
    val primary = MaterialTheme.colorScheme.primary
    val (title, subtitle, icon) = when (selectedTab) {
        0 -> Triple("No active orders yet", "Your live deliveries and pickup updates will show here.", Icons.Filled.DeliveryDining)
        1 -> Triple("No past orders yet", "Completed meals and reorder history will appear here.", Icons.Filled.History)
        else -> Triple("No cancelled orders", "If an order is cancelled, you will find it here.", Icons.Filled.Cancel)
    }
    Box(modifier = Modifier.fillMaxSize().padding(24.dp), contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(18.dp)).border(1.dp, Line, RoundedCornerShape(18.dp)).padding(horizontal = 22.dp, vertical = 26.dp)) {
            Box(modifier = Modifier.size(72.dp).background(primary.copy(alpha = 0.10f), CircleShape), contentAlignment = Alignment.Center) {
                Icon(icon, contentDescription = null, tint = primary, modifier = Modifier.size(34.dp))
            }
            Spacer(modifier = Modifier.height(18.dp))
            Text(title, color = Ink, fontSize = 18.sp, fontWeight = FontWeight.Black)
            Text(subtitle, color = Muted, fontSize = 12.5.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 8.dp))
        }
    }
}

@Composable
private fun OrderCard(order: OrderSummaryListItemDto, onClick: () -> Unit) {
    val style = statusStyle(order.status)
    val isLive = isOngoing(order)
    val actionLabel = if (isDelivered(order)) "Reorder" else if (isLive) "Track Order" else "View Details"

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(elevation = 7.dp, shape = RoundedCornerShape(14.dp), ambientColor = Color.Black.copy(alpha = 0.055f))
            .background(Color.White, RoundedCornerShape(14.dp))
            .border(1.dp, Color(0xFFE8E8EE), RoundedCornerShape(14.dp))
            .clickable(onClick = onClick)
            .padding(14.dp),
    ) {
        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
            StatusChip(style)
            Spacer(modifier = Modifier.weight(1f))
            order.createdAt?.let {
                Text(it, color = Color(0xFF62636A), fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Spacer(modifier = Modifier.width(8.dp))
            }
            Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = Color(0xFF56575E), modifier = Modifier.size(25.dp))
        }
        Spacer(modifier = Modifier.height(14.dp))
        Row(verticalAlignment = Alignment.Top) {
            RestaurantThumb(order.restaurantName)
            Spacer(modifier = Modifier.width(14.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(order.restaurantName ?: "Restaurant", color = Color.Black, fontSize = 14.5.sp, fontWeight = FontWeight.Black, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Spacer(modifier = Modifier.height(6.dp))
                Text("Order ID: #${order.orderNumber ?: order.id}", color = Color(0xFF5E5F66), fontSize = 12.5.sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Spacer(modifier = Modifier.height(8.dp))
                Text("Total - Rs ${"%.0f".format(order.total ?: 0.0)}", color = Color(0xFF3E3F46), fontSize = 13.sp, fontWeight = FontWeight.Bold)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = Color(0xFF56575E), modifier = Modifier.size(25.dp))
        }
        Spacer(modifier = Modifier.height(16.dp))
        Row(verticalAlignment = Alignment.CenterVertically) {
            StatusLine(order, Modifier.weight(1f))
            Spacer(modifier = Modifier.width(12.dp))
            Box(
                modifier = Modifier.height(42.dp).background(if (isLive) MaterialTheme.colorScheme.primary.copy(alpha = 0.13f) else Color.White, RoundedCornerShape(999.dp)).border(1.dp, MaterialTheme.colorScheme.primary, RoundedCornerShape(999.dp)).padding(horizontal = 18.dp),
                contentAlignment = Alignment.Center,
            ) {
                Text(actionLabel, color = MaterialTheme.colorScheme.primary, fontSize = 12.sp, fontWeight = FontWeight.Black)
            }
        }
    }
}

@Composable
private fun StatusChip(style: StatusStyle) {
    Box(modifier = Modifier.background(style.bg, RoundedCornerShape(6.dp)).padding(horizontal = 10.dp, vertical = 6.dp)) {
        Text(style.label, color = style.fg, fontSize = 11.5.sp, fontWeight = FontWeight.Black, letterSpacing = 0.2.sp)
    }
}

@Composable
private fun StatusLine(order: OrderSummaryListItemDto, modifier: Modifier = Modifier) {
    val style = statusStyle(order.status)
    Column(modifier = modifier) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(style.icon, contentDescription = null, tint = style.fg, modifier = Modifier.size(18.dp))
            Spacer(modifier = Modifier.width(7.dp))
            Text(orderPrimaryLine(order), color = style.fg, fontSize = 13.sp, fontWeight = FontWeight.Black, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
        val secondary = orderSecondaryLine(order)
        if (secondary.isNotEmpty()) {
            Text(secondary, color = Color(0xFF67686F), fontSize = 12.sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.padding(start = 25.dp, top = 6.dp))
        }
    }
}

@Composable
private fun RestaurantThumb(name: String?) {
    val label = initialsFor(name)
    Box(modifier = Modifier.size(74.dp).clip(RoundedCornerShape(13.dp)).background(MaterialTheme.colorScheme.primary.copy(alpha = 0.10f)), contentAlignment = Alignment.Center) {
        Text(label, color = MaterialTheme.colorScheme.primary, fontSize = if (label.length <= 2) 18.sp else 12.sp, fontWeight = FontWeight.Black, textAlign = androidx.compose.ui.text.style.TextAlign.Center)
    }
}

private fun orderPrimaryLine(order: OrderSummaryListItemDto): String {
    val eta = order.estimatedDeliveryLabel ?: order.estimatedDeliveryMinutes?.let { "$it-${it + 5} mins" }
    return when {
        isCancelled(order) -> "Cancelled"
        isDelivered(order) -> "Delivered on time"
        order.status in setOf("on_the_way", "picked_up") -> eta?.let { "Arriving in $it" } ?: "Order on the way"
        else -> eta?.let { "Estimated delivery: $it" } ?: "Estimated delivery"
    }
}

private fun orderSecondaryLine(order: OrderSummaryListItemDto): String = when {
    isCancelled(order) -> ""
    isDelivered(order) -> ""
    order.status in setOf("on_the_way", "picked_up") -> "Your order is on the way"
    else -> statusTitle(order.status)
}

private fun statusTitle(status: String?): String = status?.replace("_", " ")?.replaceFirstChar { it.uppercase() } ?: "Confirmed"

private fun initialsFor(name: String?): String {
    val words = name.orEmpty().trim().split(Regex("\\s+")).filter { it.isNotBlank() }
    val label = words.take(2).joinToString("") { it.take(1).uppercase() }
    return label.ifBlank { "FOOD" }
}