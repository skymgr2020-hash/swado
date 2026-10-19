package com.renjo.app.ui.screens.profile

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Celebration
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Gavel
import androidx.compose.material.icons.filled.HeadsetMic
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material.icons.filled.PrivacyTip
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.TableRestaurant
import androidx.compose.material.icons.filled.Wallet
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import com.renjo.app.data.network.dto.UserDto
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted

private val ProfileAccent = Color(0xFFFF6A00)

@Composable
fun ProfileTabScreen(
    viewModel: ProfileViewModel = hiltViewModel(),
    onLoggedOut: () -> Unit,
    onOpenWallet: () -> Unit,
    onOpenDining: () -> Unit,
    onOpenOffers: () -> Unit,
    onOpenOrders: () -> Unit,
    onBackHome: () -> Unit = {},
) {
    val user by viewModel.currentUser.collectAsState()

    Surface(modifier = Modifier.fillMaxSize(), color = Color.White) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(start = 18.dp, top = 14.dp, end = 18.dp, bottom = 120.dp),
        ) {
            ProfileTopBar(onBackHome = onBackHome)
            Spacer(modifier = Modifier.height(22.dp))
            ProfileHero(user = user)
            Spacer(modifier = Modifier.height(22.dp))
            ProfileGroupLabel("QUICK ACCESS")
            Spacer(modifier = Modifier.height(10.dp))
            ProfileSectionCard {
                ProfileMenuTile(Icons.Filled.LocationOn, "My Addresses", "Manage saved delivery addresses")
                ProfileMenuTile(Icons.Filled.Wallet, "Payments", "Wallet, refunds and payment methods", onTap = onOpenWallet)
                ProfileMenuTile(Icons.Filled.Favorite, "Favourites", "Saved restaurants and dishes")
                ProfileMenuTile(Icons.Filled.Celebration, "My Offers", "Active coupons and promotions", onTap = onOpenOffers)
                ProfileMenuTile(Icons.Filled.Bookmark, "Scratch Cards", "View and scratch your reward coupons")
            }
            Spacer(modifier = Modifier.height(22.dp))
            ProfileGroupLabel("MY ACTIVITY")
            Spacer(modifier = Modifier.height(10.dp))
            ProfileSectionCard {
                ProfileMenuTile(Icons.Filled.History, "Order History", "Past orders and live delivery updates", onTap = onOpenOrders)
                ProfileMenuTile(Icons.Filled.TableRestaurant, "Table Bookings", "Dining reservations and upcoming visits", onTap = onOpenDining)
            }
            Spacer(modifier = Modifier.height(22.dp))
            ProfileGroupLabel("SUPPORT & INFO")
            Spacer(modifier = Modifier.height(10.dp))
            ProfileSectionCard {
                ProfileMenuTile(Icons.Filled.HeadsetMic, "Help & Support", "FAQs, contact us and more")
                ProfileMenuTile(Icons.Filled.PrivacyTip, "Privacy Policy", "Learn how we protect your data")
                ProfileMenuTile(Icons.Filled.Gavel, "Terms & Conditions", "Read our terms and conditions")
            }
            Spacer(modifier = Modifier.height(22.dp))
            ProfileGroupLabel("ACCOUNT")
            Spacer(modifier = Modifier.height(10.dp))
            ProfileSectionCard {
                ProfileMenuTile(
                    icon = Icons.AutoMirrored.Filled.Logout,
                    title = "Logout",
                    subtitle = "Sign out from your account",
                    iconColor = Danger,
                    iconBackground = Color(0xFFFFEEEE),
                    titleColor = Danger,
                    onTap = { viewModel.logout(onLoggedOut) },
                )
                ProfileMenuTile(
                    icon = Icons.Filled.Delete,
                    title = "Delete Account",
                    subtitle = "Permanently remove your account access",
                    iconColor = Danger,
                    iconBackground = Color(0xFFFFEEEE),
                    titleColor = Danger,
                )
            }
        }
    }
}

@Composable
private fun ProfileTopBar(onBackHome: () -> Unit) {
    Row(verticalAlignment = Alignment.Top, modifier = Modifier.fillMaxWidth()) {
        RoundHeaderButton(Icons.AutoMirrored.Filled.ArrowBack, onTap = onBackHome)
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text("My Profile", color = Color.Black, fontSize = 20.sp, fontWeight = FontWeight.Black)
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                "Manage your account and preferences",
                color = Color(0xFF676875),
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        RoundHeaderButton(Icons.Filled.Notifications, showDot = true)
        Spacer(modifier = Modifier.width(12.dp))
        RoundHeaderButton(Icons.Filled.Settings)
    }
}

@Composable
private fun RoundHeaderButton(icon: ImageVector, showDot: Boolean = false, onTap: () -> Unit = {}) {
    Box(modifier = Modifier.size(44.dp).clickable(onClick = onTap), contentAlignment = Alignment.Center) {
        Icon(icon, contentDescription = null, tint = Color.Black, modifier = Modifier.size(29.dp))
        if (showDot) {
            Box(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(top = 8.dp, end = 9.dp)
                    .size(10.dp)
                    .background(Color(0xFFFF3B1F), CircleShape)
                    .border(1.4.dp, Color.White, CircleShape),
            )
        }
    }
}

@Composable
private fun ProfileHero(user: UserDto?) {
    val name = user?.name?.trim()?.takeIf { it.isNotEmpty() } ?: "Guest User"
    val phone = user?.phone?.trim().orEmpty()
    val photoUrl = user?.profileImage ?: user?.profilePhotoUrl

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(elevation = 6.dp, shape = RoundedCornerShape(16.dp), ambientColor = Color.Black.copy(alpha = 0.025f))
            .background(Color.White, RoundedCornerShape(16.dp))
            .border(1.dp, Color(0xFFE9E2DD), RoundedCornerShape(16.dp))
            .padding(start = 14.dp, top = 14.dp, end = 14.dp, bottom = 12.dp),
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            ProfilePhoto(name = name, photoUrl = photoUrl)
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(name, color = Ink, fontSize = 17.sp, fontWeight = FontWeight.Black, maxLines = 1, overflow = TextOverflow.Ellipsis)
                if (phone.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(6.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Filled.Phone, contentDescription = null, tint = Muted, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(phone, color = Muted, fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
            }
            Spacer(modifier = Modifier.width(8.dp))
            Box(
                modifier = Modifier
                    .size(34.dp)
                    .background(Color(0xFFFFF2E8), RoundedCornerShape(12.dp))
                    .border(1.dp, Color(0xFFFFDFC8), RoundedCornerShape(12.dp)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(Icons.Filled.Edit, contentDescription = null, tint = ProfileAccent, modifier = Modifier.size(17.dp))
            }
        }
        Spacer(modifier = Modifier.height(12.dp))
        StatsPanel()
    }
}

@Composable
private fun ProfilePhoto(name: String, photoUrl: String?) {
    Box(modifier = Modifier.size(66.dp)) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .clip(CircleShape)
                .background(Brush.verticalGradient(listOf(Color(0xFFFFE6D7), Color(0xFFFFC093)))),
            contentAlignment = Alignment.Center,
        ) {
            if (!photoUrl.isNullOrBlank()) {
                AsyncImage(model = photoUrl, contentDescription = name, contentScale = ContentScale.Crop, modifier = Modifier.fillMaxSize())
            } else {
                Text(name.take(1).uppercase(), color = MaterialTheme.colorScheme.primary, fontSize = 22.sp, fontWeight = FontWeight.Black)
            }
        }
        Box(
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .size(24.dp)
                .background(Color.White, CircleShape)
                .padding(2.dp)
                .background(ProfileAccent, CircleShape),
            contentAlignment = Alignment.Center,
        ) {
            Icon(Icons.Filled.Edit, contentDescription = null, tint = Color.White, modifier = Modifier.size(12.dp))
        }
    }
}

@Composable
private fun StatsPanel() {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(Color(0xFFFFFAF6), RoundedCornerShape(12.dp))
            .border(1.dp, Color(0xFFF0E3DA), RoundedCornerShape(12.dp))
            .padding(horizontal = 8.dp, vertical = 9.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        ProfileStat(Icons.Filled.Restaurant, "--", "Orders", Modifier.weight(1f))
        StatDivider()
        ProfileStat(Icons.Filled.Payments, "Rs 0", "Wallet", Modifier.weight(1f))
        StatDivider()
        ProfileStat(Icons.Filled.Celebration, "--", "Offers", Modifier.weight(1f))
        StatDivider()
        ProfileStat(Icons.Filled.Favorite, "--", "Saved", Modifier.weight(1f))
    }
}

@Composable
private fun StatDivider() {
    Box(modifier = Modifier.width(1.dp).height(34.dp).background(Color(0xFFE0E0E4)))
}

@Composable
private fun ProfileStat(icon: ImageVector, value: String, label: String, modifier: Modifier) {
    Column(modifier = modifier, horizontalAlignment = Alignment.CenterHorizontally) {
        Icon(icon, contentDescription = null, tint = ProfileAccent, modifier = Modifier.size(16.dp))
        Spacer(modifier = Modifier.height(4.dp))
        Text(value, color = Ink, fontSize = 13.sp, fontWeight = FontWeight.Black, maxLines = 1, overflow = TextOverflow.Ellipsis)
        Spacer(modifier = Modifier.height(3.dp))
        Text(label, color = Muted, fontSize = 10.5.sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

@Composable
private fun ProfileGroupLabel(label: String) {
    Text(label, color = Muted, fontSize = 11.5.sp, fontWeight = FontWeight.ExtraBold, letterSpacing = 0.8.sp, modifier = Modifier.padding(start = 4.dp))
}

@Composable
private fun ProfileSectionCard(content: @Composable ColumnScope.() -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(Color.White, RoundedCornerShape(16.dp))
            .border(1.dp, Color(0xFFE9E2DD), RoundedCornerShape(16.dp))
            .padding(vertical = 4.dp),
        content = content,
    )
}

@Composable
private fun ProfileMenuTile(
    icon: ImageVector,
    title: String,
    subtitle: String,
    iconColor: Color = MaterialTheme.colorScheme.primary,
    iconBackground: Color = MaterialTheme.colorScheme.primary.copy(alpha = 0.10f),
    titleColor: Color = Ink,
    onTap: (() -> Unit)? = null,
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .clickable(enabled = onTap != null) { onTap?.invoke() }
            .padding(horizontal = 12.dp, vertical = 10.dp),
    ) {
        Box(modifier = Modifier.size(38.dp).background(iconBackground, RoundedCornerShape(13.dp)), contentAlignment = Alignment.Center) {
            Icon(icon, contentDescription = null, tint = iconColor, modifier = Modifier.size(20.dp))
        }
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(title, color = titleColor, fontSize = 14.sp, fontWeight = FontWeight.Black, maxLines = 1)
            Text(subtitle, color = Muted, fontSize = 11.5.sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
        Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = Color(0xFFB5B5BE), modifier = Modifier.size(22.dp))
    }
}