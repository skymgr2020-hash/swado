package com.renjo.app.ui.screens.restaurant

import androidx.compose.foundation.Image
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
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import com.renjo.app.data.network.dto.AddOnDto
import com.renjo.app.data.network.dto.MenuItemDto
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Line
import com.renjo.app.ui.theme.Muted

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RestaurantDetailScreen(
    viewModel: RestaurantDetailViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onViewCart: () -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    val cart by viewModel.cart.collectAsState()
    var addOnSheetItem by remember { mutableStateOf<MenuItemDto?>(null) }

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Box(modifier = Modifier.fillMaxSize()) {
            when (val state = uiState) {
                is MenuUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                is MenuUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Couldn't load the menu", color = Danger, fontWeight = FontWeight.Bold)
                        Text(state.message, color = Muted, fontSize = 12.sp)
                        OutlinedButton(onClick = viewModel::load, modifier = Modifier.padding(top = 12.dp)) { Text("Retry") }
                    }
                }
                is MenuUiState.Loaded -> {
                    val grouped = state.menu.menuItems.groupBy { it.categoryName ?: "Menu" }
                    LazyColumn(contentPadding = PaddingValues(bottom = if (cart != null) 96.dp else 24.dp)) {
                        item(key = "header") {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier.fillMaxWidth().background(MaterialTheme.colorScheme.primary).padding(16.dp),
                            ) {
                                IconButton(onClick = onBack) {
                                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color.White)
                                }
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(state.menu.restaurant.name, color = Color.White, fontSize = 20.sp, fontWeight = FontWeight.Black)
                            }
                        }
                        for ((category, itemsInCategory) in grouped) {
                            item(key = "cat_$category") {
                                Text(
                                    text = category,
                                    color = Ink,
                                    fontSize = 16.sp,
                                    fontWeight = FontWeight.Black,
                                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                                )
                            }
                            items(itemsInCategory, key = { it.id }) { menuItem ->
                                MenuItemRow(
                                    menuItem = menuItem,
                                    quantity = viewModel.quantityOf(menuItem.id),
                                    onAdd = {
                                        if (menuItem.addOns.isNotEmpty()) {
                                            addOnSheetItem = menuItem
                                        } else {
                                            viewModel.addToCart(menuItem)
                                        }
                                    },
                                    onDecrement = { viewModel.decrementSimple(menuItem) },
                                )
                            }
                        }
                    }
                }
            }

            cart?.let { restaurantCart ->
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(16.dp)
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(16.dp))
                        .background(MaterialTheme.colorScheme.primary)
                        .clickable(onClick = onViewCart)
                        .padding(horizontal = 20.dp, vertical = 16.dp),
                ) {
                    Icon(Icons.Filled.ShoppingCart, contentDescription = null, tint = Color.White)
                    Spacer(modifier = Modifier.width(10.dp))
                    Text("${restaurantCart.totalQuantity} item${if (restaurantCart.totalQuantity == 1) "" else "s"}", color = Color.White, fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.weight(1f))
                    Text("View Cart • ₹${"%.0f".format(restaurantCart.subtotal)}", color = Color.White, fontWeight = FontWeight.Black)
                }
            }
        }
    }

    addOnSheetItem?.let { menuItem ->
        AddOnSheet(
            menuItem = menuItem,
            onDismiss = { addOnSheetItem = null },
            onConfirm = { selectedAddOns, quantity ->
                viewModel.addToCart(menuItem, quantity, selectedAddOns)
                addOnSheetItem = null
            },
        )
    }
}

@Composable
private fun MenuItemRow(menuItem: MenuItemDto, quantity: Int, onAdd: () -> Unit, onDecrement: () -> Unit) {
    Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp)) {
        Column(modifier = Modifier.weight(1f)) {
            VegDot(menuItem.isVeg)
            Spacer(modifier = Modifier.height(4.dp))
            Text(menuItem.name, color = Ink, fontSize = 15.sp, fontWeight = FontWeight.Bold)
            if (menuItem.ratingValue > 0) {
                Text("★ ${menuItem.ratingValue}", color = Muted, fontSize = 11.sp)
            }
            Spacer(modifier = Modifier.height(4.dp))
            Row {
                if (menuItem.hasDiscount) {
                    Text(
                        "₹${menuItem.basePrice.toInt()}",
                        color = Muted,
                        fontSize = 13.sp,
                        textDecoration = TextDecoration.LineThrough,
                        modifier = Modifier.padding(end = 6.dp),
                    )
                }
                Text("₹${menuItem.effectivePrice.toInt()}", color = Ink, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
            }
            menuItem.description?.let {
                Text(it, color = Muted, fontSize = 12.sp, maxLines = 2, modifier = Modifier.padding(top = 4.dp))
            }
        }
        Spacer(modifier = Modifier.width(12.dp))
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            if (menuItem.imageUrl != null) {
                AsyncImage(
                    model = menuItem.imageUrl,
                    contentDescription = menuItem.name,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.size(96.dp).clip(RoundedCornerShape(12.dp)),
                )
            } else {
                Box(modifier = Modifier.size(96.dp))
            }
            Spacer(modifier = Modifier.height(8.dp))
            if (quantity > 0) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .background(Color.White, RoundedCornerShape(8.dp))
                        .border(1.dp, MaterialTheme.colorScheme.primary, RoundedCornerShape(8.dp)),
                ) {
                    IconButton(onClick = onDecrement, modifier = Modifier.size(32.dp)) {
                        Icon(Icons.Filled.Remove, contentDescription = "Remove", tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
                    }
                    Text("$quantity", fontWeight = FontWeight.Bold, color = Ink)
                    IconButton(onClick = onAdd, modifier = Modifier.size(32.dp)) {
                        Icon(Icons.Filled.Add, contentDescription = "Add", tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
                    }
                }
            } else {
                OutlinedButton(onClick = onAdd, contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp)) {
                    Text("ADD", fontWeight = FontWeight.Black, fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
private fun VegDot(isVeg: Boolean) {
    val color = if (isVeg) Color(0xFF16A34A) else Color(0xFFE53935)
    Box(
        modifier = Modifier
            .size(14.dp)
            .border(1.5.dp, color, RoundedCornerShape(3.dp)),
        contentAlignment = Alignment.Center,
    ) {
        Box(modifier = Modifier.size(7.dp).background(color, CircleShape))
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun AddOnSheet(menuItem: MenuItemDto, onDismiss: () -> Unit, onConfirm: (List<AddOnDto>, Int) -> Unit) {
    val selected = remember { mutableStateOf(setOf<AddOnDto>()) }
    var quantity by remember { mutableStateOf(1) }

    ModalBottomSheet(onDismissRequest = onDismiss) {
        Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp).padding(bottom = 24.dp)) {
            Text(menuItem.name, color = Ink, fontSize = 18.sp, fontWeight = FontWeight.Black)
            Text("Customise as per your taste", color = Muted, fontSize = 12.sp)
            Spacer(modifier = Modifier.height(12.dp))
            for (addOn in menuItem.addOns.filter { it.isAvailable }) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.fillMaxWidth().clickable {
                        selected.value = if (addOn in selected.value) selected.value - addOn else selected.value + addOn
                    },
                ) {
                    Checkbox(checked = addOn in selected.value, onCheckedChange = { checked ->
                        selected.value = if (checked) selected.value + addOn else selected.value - addOn
                    })
                    Text(addOn.name, color = Ink, fontSize = 14.sp, modifier = Modifier.weight(1f))
                    Text("+₹${addOn.price.toInt()}", color = Muted, fontSize = 13.sp)
                }
            }
            Spacer(modifier = Modifier.height(16.dp))
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.border(1.dp, Line, RoundedCornerShape(8.dp)),
                ) {
                    IconButton(onClick = { if (quantity > 1) quantity-- }) {
                        Icon(Icons.Filled.Remove, contentDescription = "Decrease")
                    }
                    Text("$quantity", fontWeight = FontWeight.Bold)
                    IconButton(onClick = { quantity++ }) {
                        Icon(Icons.Filled.Add, contentDescription = "Increase")
                    }
                }
                Spacer(modifier = Modifier.width(16.dp))
                val total = (menuItem.effectivePrice + selected.value.sumOf { it.price }) * quantity
                androidx.compose.material3.Button(
                    onClick = { onConfirm(selected.value.toList(), quantity) },
                    modifier = Modifier.weight(1f),
                ) {
                    Text("Add item • ₹${total.toInt()}", fontWeight = FontWeight.Black)
                }
            }
        }
    }
}
