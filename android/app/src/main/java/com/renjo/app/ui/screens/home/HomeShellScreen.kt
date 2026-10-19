package com.renjo.app.ui.screens.home

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.renjo.app.R
import com.renjo.app.ui.screens.orders.OrdersTabScreen
import com.renjo.app.ui.screens.profile.ProfileTabScreen
import com.renjo.app.ui.screens.search.SearchTabScreen
import com.renjo.app.ui.theme.Muted

/**
 * Floating rounded pill nav bar, visually matching the Flutter app's
 * _HomeBottomNavBar: white 28dp pill, 74dp height, raised center Offer action,
 * and four persistent destinations around it.
 */
@Composable
fun HomeShellScreen(
    onLoggedOut: () -> Unit,
    onOpenRestaurant: (Int) -> Unit,
    onOpenOrder: (Int) -> Unit,
    onOpenWallet: () -> Unit,
    onOpenDining: () -> Unit,
    onOpenOffers: () -> Unit,
) {
    var currentIndex by remember { mutableIntStateOf(0) }

    Box(modifier = Modifier.fillMaxSize()) {
        when (currentIndex) {
            0 -> HomeTabScreen(onSearchTap = { currentIndex = 1 }, onOpenRestaurant = onOpenRestaurant)
            1 -> SearchTabScreen()
            2 -> OrdersTabScreen(onOpenOrder = onOpenOrder, onBackHome = { currentIndex = 0 })
            else -> ProfileTabScreen(
                onLoggedOut = onLoggedOut,
                onOpenWallet = onOpenWallet,
                onOpenDining = onOpenDining,
                onOpenOffers = onOpenOffers,
                onOpenOrders = { currentIndex = 2 },
                onBackHome = { currentIndex = 0 },
            )
        }

        Box(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
                .height(112.dp),
            contentAlignment = Alignment.BottomCenter,
        ) {
            Row(
                modifier = Modifier
                    .padding(horizontal = 16.dp, vertical = 8.dp)
                    .fillMaxWidth()
                    .height(74.dp)
                    .shadow(elevation = 14.dp, shape = RoundedCornerShape(28.dp))
                    .background(Color.White, RoundedCornerShape(28.dp))
                    .padding(horizontal = 10.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                NavItem(Icons.Filled.Home, "Home", currentIndex == 0, Modifier.weight(1f)) { currentIndex = 0 }
                NavItem(Icons.Filled.Search, "Search", currentIndex == 1, Modifier.weight(1f)) { currentIndex = 1 }
                CenterOfferNavItem(Modifier.weight(1f), onOpenOffers)
                NavItem(Icons.Filled.Receipt, "Orders", currentIndex == 2, Modifier.weight(1f)) { currentIndex = 2 }
                NavItem(Icons.Filled.Person, "Profile", currentIndex == 3, Modifier.weight(1f)) { currentIndex = 3 }
            }
        }
    }
}

@Composable
private fun NavItem(
    icon: ImageVector,
    label: String,
    active: Boolean,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
) {
    val color = if (active) MaterialTheme.colorScheme.primary else Muted
    Box(
        modifier = modifier
            .fillMaxSize()
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center,
    ) {
        Column(
            modifier = Modifier
                .width(58.dp)
                .height(54.dp)
                .background(
                    if (active) Color(0xFFF1E7FF) else Color.Transparent,
                    RoundedCornerShape(24.dp),
                ),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            Icon(icon, contentDescription = label, tint = color, modifier = Modifier.size(22.dp))
            Text(
                label,
                color = color,
                fontSize = 10.5.sp,
                fontWeight = if (active) FontWeight.ExtraBold else FontWeight.SemiBold,
                maxLines = 1,
            )
        }
    }
}

@Composable
private fun CenterOfferNavItem(modifier: Modifier = Modifier, onClick: () -> Unit) {
    Box(
        modifier = modifier
            .fillMaxSize()
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center,
    ) {
        Box(
            modifier = Modifier
                .offset(y = (-20).dp)
                .size(66.dp)
                .shadow(elevation = 18.dp, shape = RoundedCornerShape(22.dp))
                .background(Color.White, RoundedCornerShape(22.dp))
                .padding(4.dp),
            contentAlignment = Alignment.Center,
        ) {
            Image(
                painter = painterResource(R.mipmap.ic_launcher),
                contentDescription = "Offer",
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .fillMaxSize()
                    .clip(RoundedCornerShape(18.dp)),
            )
        }
        Text(
            text = "Offer",
            color = MaterialTheme.colorScheme.primary,
            fontSize = 11.5.sp,
            fontWeight = FontWeight.Black,
            maxLines = 1,
            modifier = Modifier.align(Alignment.BottomCenter).padding(bottom = 6.dp),
        )
    }
}