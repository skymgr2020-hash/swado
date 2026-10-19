package com.renjo.app.ui.screens.home

import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.Timer
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import com.renjo.app.data.network.dto.BannerDto
import com.renjo.app.data.network.dto.CuisineItemDto
import com.renjo.app.data.network.dto.DealItemDto
import com.renjo.app.data.network.dto.HomeSectionDto
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted
import kotlinx.serialization.json.Json
import kotlinx.coroutines.delay

private val HomeGreen = Color(0xFF16A34A)
private val HomeLightGreen = Color(0xFFEAF8EA)
private val HomePeach = Color(0xFFFFE9DD)
private val CardLine = Color(0xFFF1F1F1)
private val homeSectionJson = Json { ignoreUnknownKeys = true; coerceInputValues = true }

/**
 * Mirrors the Flutter home screen's section-driven architecture: renders
 * whatever the admin's Home Section Management configures, in the order
 * GET /home/sections returns it — not a hardcoded static layout. The
 * "restaurant_discovery" section's slot is filled from a separate
 * /restaurants/nearby fetch, and the hero banner from /api/banners
 * (see HomeTabViewModel kdoc for why both are separate calls).
 */
@Composable
fun HomeTabScreen(
    viewModel: HomeTabViewModel = hiltViewModel(),
    onSearchTap: () -> Unit = {},
    onNotificationTap: () -> Unit = {},
    onOpenRestaurant: (Int) -> Unit = {},
) {
    val feedState by viewModel.feedState.collectAsState()
    val vegOnly by viewModel.vegOnly.collectAsState()
    var showLocationPicker by remember { mutableStateOf(false) }

    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) {
        // Granted or denied, reload — the ViewModel falls back to a fixed
        // location if permission wasn't granted, so this is safe either way.
        viewModel.loadRestaurants()
    }
    LaunchedEffect(Unit) {
        permissionLauncher.launch(Manifest.permission.ACCESS_FINE_LOCATION)
    }

    if (showLocationPicker) {
        LocationPickerSheet(
            onDismiss = { showLocationPicker = false },
            onUseCurrentLocation = {
                val device = viewModel.currentDeviceLocation()
                if (device != null) viewModel.selectLocation(device, label = null) else viewModel.loadRestaurants()
                showLocationPicker = false
            },
            onSearch = { query -> viewModel.searchPlaces(query) },
            onSelect = { suggestion ->
                viewModel.selectLocation(suggestion.location, suggestion.cityLabel)
                showLocationPicker = false
            },
        )
    }

    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        when (val state = feedState) {
            is HomeFeedState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            is HomeFeedState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("Couldn't load restaurants", color = Danger, fontWeight = FontWeight.Bold)
                    Text(state.message, color = Muted, fontSize = 12.sp)
                    Button(onClick = viewModel::loadRestaurants, modifier = Modifier.padding(top = 12.dp)) {
                        Text("Retry")
                    }
                }
            }
            is HomeFeedState.Loaded -> {
                val restaurants = if (vegOnly) state.restaurants.filter { it.isPureVeg } else state.restaurants
                HomeSectionList(
                    banners = state.banners,
                    sections = state.sections,
                    restaurants = restaurants,
                    locationLabel = state.locationLabel,
                    vegOnly = vegOnly,
                    onVegToggle = viewModel::toggleVegOnly,
                    onLocationTap = { showLocationPicker = true },
                    onSearchTap = onSearchTap,
                    onNotificationTap = onNotificationTap,
                    onOpenRestaurant = onOpenRestaurant,
                )
            }
        }
    }
}

@Composable
private fun HomeSectionList(
    banners: List<BannerDto>,
    sections: List<HomeSectionDto>,
    restaurants: List<RestaurantDto>,
    locationLabel: String,
    vegOnly: Boolean,
    onVegToggle: () -> Unit,
    onLocationTap: () -> Unit,
    onSearchTap: () -> Unit,
    onNotificationTap: () -> Unit,
    onOpenRestaurant: (Int) -> Unit,
) {
    LazyColumn(contentPadding = PaddingValues(bottom = 96.dp)) {
        item(key = "hero_header") {
            HeroHeaderSection(
                banners = banners,
                locationLabel = locationLabel,
                vegOnly = vegOnly,
                onVegToggle = onVegToggle,
                onLocationTap = onLocationTap,
                onSearchTap = onSearchTap,
                onNotificationTap = onNotificationTap,
            )
        }
        for (section in sections) {
            when (section.type) {
                "categories" -> item(key = section.token) { CategoriesSection(section) }
                "deals_for_you" -> item(key = section.token) { DealsSection(section) }
                "restaurant_discovery" -> {
                    item(key = "${section.token}_header") { SectionHeader(section) }
                    if (restaurants.isEmpty()) {
                        item(key = "${section.token}_empty") {
                            Text("No restaurants found nearby yet", color = Muted, modifier = Modifier.padding(24.dp))
                        }
                    } else {
                        items(restaurants, key = { it.id }) { restaurant ->
                            Box(
                                modifier = Modifier
                                    .padding(horizontal = 16.dp, vertical = 8.dp)
                                    .clickable { onOpenRestaurant(restaurant.id) },
                            ) {
                                RestaurantListTile(restaurant)
                            }
                        }
                    }
                }
                // menu_price_filter_config and any other admin-configured
                // section types render as nothing for now — Phase 2 covers
                // the section types actually seeded on swado.online.
                else -> Unit
            }
        }
    }
}

/**
 * Pixel-matched to the Flutter app's _HomeHeroPinnedDelegate composition:
 * the promo banner carousel fills the top as a full-bleed edge-to-edge
 * background (no rounded corners, no side padding — unlike a plain content
 * card), with a dark top-to-bottom gradient scrim for legibility, and the
 * location bar, search bar, and veg toggle all overlaid on top of it as one
 * visual unit. The Flutter version pins/collapses this on scroll (a sliver
 * header); this is a simpler non-collapsing version that still matches the
 * requested "banner behind header controls" composition. Falls back to a
 * solid brand-color background when there are no banners.
 */
@Composable
private fun HeroHeaderSection(
    banners: List<BannerDto>,
    locationLabel: String,
    vegOnly: Boolean,
    onVegToggle: () -> Unit,
    onLocationTap: () -> Unit,
    onSearchTap: () -> Unit,
    onNotificationTap: () -> Unit,
) {
    val pagerState = rememberPagerState(pageCount = { banners.size.coerceAtLeast(1) })

    LaunchedEffect(banners.size) {
        if (banners.size <= 1) return@LaunchedEffect
        while (true) {
            delay(5000)
            val next = (pagerState.currentPage + 1) % banners.size
            pagerState.animateScrollToPage(next)
        }
    }

    Box(modifier = Modifier.fillMaxWidth().height(360.dp)) {
        if (banners.isEmpty()) {
            Box(modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.primary))
        } else {
            HorizontalPager(state = pagerState, modifier = Modifier.fillMaxSize()) { page ->
                val banner = banners[page]
                if (banner.imageUrl != null) {
                    Box(modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.primary)) {
                        // BoxFit-style contain, not crop — the real banner
                        // art has text near the edges (e.g. "FREE DELIVERY
                        // ON FIRST ORDER") that a cover/crop scale would cut off.
                        AsyncImage(
                            model = banner.imageUrl,
                            contentDescription = banner.title,
                            contentScale = ContentScale.Fit,
                            modifier = Modifier.fillMaxSize(),
                        )
                    }
                } else {
                    Box(modifier = Modifier.fillMaxSize().background(MaterialTheme.colorScheme.primary))
                }
            }
            if (banners.size > 1) {
                Row(
                    horizontalArrangement = Arrangement.Center,
                    modifier = Modifier.align(Alignment.BottomCenter).padding(bottom = 10.dp).fillMaxWidth(),
                ) {
                    repeat(banners.size) { index ->
                        Box(
                            modifier = Modifier
                                .padding(horizontal = 3.dp)
                                .size(if (index == pagerState.currentPage) 8.dp else 6.dp)
                                .clip(CircleShape)
                                .background(if (index == pagerState.currentPage) Color.White else Color.White.copy(alpha = 0.5f)),
                        )
                    }
                }
            }
        }

        // Scrim so white overlay text/controls stay legible over any image.
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        listOf(Color.Black.copy(alpha = 0.45f), Color.Black.copy(alpha = 0.12f), Color.Transparent),
                    ),
                ),
        )

        Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp).padding(top = 16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.weight(1f).clickable(onClick = onLocationTap),
                ) {
                    Icon(Icons.Filled.LocationOn, contentDescription = null, tint = Color.White)
                    Spacer(modifier = Modifier.width(8.dp))
                    Column {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(locationLabel, color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Black)
                            Icon(Icons.Filled.ArrowDropDown, contentDescription = null, tint = Color.White)
                        }
                        Text("Tap to change delivery location", color = Color.White.copy(alpha = 0.85f), fontSize = 12.sp)
                    }
                }
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(Color.White.copy(alpha = 0.18f))
                        .clickable(onClick = onNotificationTap),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(Icons.Filled.Notifications, contentDescription = "Notifications", tint = Color.White, modifier = Modifier.size(20.dp))
                }
            }
            Spacer(modifier = Modifier.height(16.dp))
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color.White, RoundedCornerShape(16.dp))
                    .padding(horizontal = 16.dp, vertical = 8.dp),
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.weight(1f).clickable(onClick = onSearchTap).padding(vertical = 6.dp),
                ) {
                    Icon(Icons.Filled.Search, contentDescription = null, tint = Muted)
                    Spacer(modifier = Modifier.width(10.dp))
                    Text("Search for restaurants, dishes...", color = Muted, fontSize = 13.sp)
                }
                Text("Veg", color = if (vegOnly) HomeGreen else Muted, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(end = 4.dp))
                Switch(
                    checked = vegOnly,
                    onCheckedChange = { onVegToggle() },
                    colors = SwitchDefaults.colors(
                        checkedThumbColor = HomeGreen,
                        checkedTrackColor = HomeLightGreen,
                        uncheckedThumbColor = Muted,
                        uncheckedTrackColor = Color.Transparent,
                        uncheckedBorderColor = Muted,
                    ),
                    modifier = Modifier.height(24.dp),
                )
            }
        }
    }
}

@Composable
private fun SectionHeader(section: HomeSectionDto) {
    Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp)) {
        section.title?.let { Text(it, color = Ink, fontSize = 16.sp, fontWeight = FontWeight.Black) }
        section.subtitle?.let { Text(it, color = Muted, fontSize = 12.sp) }
    }
}

@Composable
private fun CategoriesSection(section: HomeSectionDto) {
    val cuisines = section.items.mapNotNull { runCatching { homeSectionJson.decodeFromJsonElement(CuisineItemDto.serializer(), it) }.getOrNull() }
    if (cuisines.isEmpty()) return
    Column {
        SectionHeader(section)
        LazyRow(contentPadding = PaddingValues(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(20.dp)) {
            items(cuisines, key = { it.id }) { cuisine ->
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.width(64.dp)) {
                    Box(
                        modifier = Modifier.size(56.dp).clip(CircleShape).background(HomePeach),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(Icons.Filled.Restaurant, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                    }
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(cuisine.name, color = Ink, fontSize = 11.sp, fontWeight = FontWeight.SemiBold, maxLines = 1)
                }
            }
        }
        Spacer(modifier = Modifier.height(12.dp))
    }
}

/**
 * Pixel-matched to the Flutter app's _OfferTile in its compact,
 * showRewardAndValidityOnly mode (the exact mode used for the deals_for_you
 * section): image or brand-color backdrop, a dark reward-label pill
 * top-left, a dark validity pill bottom.
 */
@Composable
private fun DealsSection(section: HomeSectionDto) {
    val deals = section.items.mapNotNull { runCatching { homeSectionJson.decodeFromJsonElement(DealItemDto.serializer(), it) }.getOrNull() }
    if (deals.isEmpty()) return
    Column {
        SectionHeader(section)
        LazyRow(contentPadding = PaddingValues(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            items(deals, key = { it.id }) { deal -> DealCard(deal) }
        }
        Spacer(modifier = Modifier.height(12.dp))
    }
}

@Composable
private fun DealCard(deal: DealItemDto) {
    val rewardLabel = when {
        deal.discountType == "percentage" && deal.discountValue != null -> "${deal.discountValue.toInt()}% OFF"
        deal.discountType != null && deal.discountValue != null -> "₹${deal.discountValue.toInt()} OFF"
        else -> deal.title.uppercase()
    }
    Box(
        modifier = Modifier
            .width(148.dp)
            .height(154.dp)
            .clip(RoundedCornerShape(16.dp)),
    ) {
        if (deal.imageUrl != null) {
            AsyncImage(model = deal.imageUrl, contentDescription = deal.title, contentScale = ContentScale.Crop, modifier = Modifier.fillMaxSize())
        } else {
            Box(
                modifier = Modifier.fillMaxSize().background(
                    Brush.linearGradient(listOf(MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.secondary)),
                ),
            )
        }
        Box(
            modifier = Modifier
                .padding(8.dp)
                .align(Alignment.TopStart)
                .background(Color.Black.copy(alpha = 0.56f), RoundedCornerShape(9.dp))
                .padding(horizontal = 7.dp, vertical = 5.dp),
        ) {
            Text(rewardLabel, color = Color.White, fontSize = 9.2.sp, fontWeight = FontWeight.Black, maxLines = 1)
        }
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier
                .padding(8.dp)
                .align(Alignment.BottomStart)
                .background(Color.Black.copy(alpha = 0.50f), RoundedCornerShape(10.dp))
                .padding(horizontal = 7.dp, vertical = 6.dp),
        ) {
            Icon(Icons.Filled.Timer, contentDescription = null, tint = Color.White, modifier = Modifier.size(12.dp))
            Spacer(modifier = Modifier.width(4.dp))
            Text(deal.title, color = Color.White, fontSize = 8.6.sp, fontWeight = FontWeight.Black, maxLines = 1)
            Icon(Icons.Filled.ChevronRight, contentDescription = null, tint = Color.White, modifier = Modifier.size(13.dp))
        }
    }
}

/**
 * Pixel-matched to the Flutter app's _RestaurantListTileModern — the widget
 * actually used for the "restaurant_discovery" section (a single-column
 * list, not a grid): 210dp banner image, a circular logo overlapping its
 * bottom edge, name/rating row, cuisine • distance line, delivery • price
 * line. (_RecommendedRestaurantCard, a small 2-column grid card, exists in
 * the Flutter source but is dead code — never actually instantiated there.)
 */
@Composable
private fun RestaurantListTile(restaurant: RestaurantDto) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(24.dp))
            .background(Color.White)
            .border(1.dp, CardLine, RoundedCornerShape(24.dp)),
    ) {
        Box {
            val imageUrl = restaurant.bannerImage ?: restaurant.logoImage
            if (imageUrl != null) {
                AsyncImage(
                    model = imageUrl,
                    contentDescription = restaurant.name,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxWidth().height(210.dp).clip(RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp)),
                )
            } else {
                Box(
                    modifier = Modifier.fillMaxWidth().height(210.dp).clip(RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp)).background(HomePeach),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(Icons.Filled.Storefront, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(40.dp))
                }
            }
            Box(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .padding(start = 14.dp)
                    .offset(y = 28.dp)
                    .size(62.dp)
                    .clip(RoundedCornerShape(18.dp))
                    .background(Color.White)
                    .padding(3.dp),
            ) {
                val logoUrl = restaurant.logoImage
                if (logoUrl != null) {
                    AsyncImage(
                        model = logoUrl,
                        contentDescription = null,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize().clip(RoundedCornerShape(15.dp)),
                    )
                } else {
                    Box(
                        modifier = Modifier.fillMaxSize().clip(RoundedCornerShape(15.dp)).background(HomePeach),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(
                            restaurant.name.trim().take(1).uppercase(),
                            color = MaterialTheme.colorScheme.primary,
                            fontSize = 20.sp,
                            fontWeight = FontWeight.Black,
                        )
                    }
                }
            }
        }
        Column(modifier = Modifier.padding(start = 14.dp, end = 14.dp, top = 38.dp, bottom = 14.dp)) {
            Row(verticalAlignment = Alignment.Top) {
                Text(
                    text = restaurant.name,
                    color = Color(0xFF1C1C1C),
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Black,
                    maxLines = 1,
                    modifier = Modifier.weight(1f),
                )
                Spacer(modifier = Modifier.width(10.dp))
                RatingBadge(restaurant.rating)
            }
            Spacer(modifier = Modifier.height(6.dp))
            val cuisineText = restaurant.cuisineText ?: restaurant.cuisine.joinToString(", ")
            val distanceText = restaurant.distance?.let { " • ${"%.1f".format(it)} km" } ?: ""
            Text(
                text = if (cuisineText.isBlank()) "Popular dishes nearby" else "$cuisineText$distanceText",
                color = Color(0xFF686B78),
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                maxLines = 1,
            )
            Spacer(modifier = Modifier.height(4.dp))
            val deliveryTime = restaurant.deliveryTime ?: 30
            val priceText = restaurant.amountForOne?.takeIf { it > 0 }?.let { " • ₹${it.toInt()} for two" } ?: ""
            Text("$deliveryTime min$priceText", color = Color(0xFF686B78), fontSize = 13.sp, fontWeight = FontWeight.Medium, maxLines = 1)
        }
    }
}

@Composable
private fun RatingBadge(rating: Double?) {
    if (rating == null || rating <= 0) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.background(HomeGreen, RoundedCornerShape(999.dp)).padding(horizontal = 8.dp, vertical = 4.dp),
        ) {
            Icon(Icons.Filled.Star, contentDescription = null, tint = Color.White, modifier = Modifier.size(12.dp))
            Spacer(modifier = Modifier.width(4.dp))
            Text("New", color = Color.White, fontSize = 11.sp, fontWeight = FontWeight.ExtraBold)
        }
    } else {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.background(HomeLightGreen, RoundedCornerShape(999.dp)).padding(horizontal = 7.dp, vertical = 3.dp),
        ) {
            Icon(Icons.Filled.Star, contentDescription = null, tint = HomeGreen, modifier = Modifier.size(12.dp))
            Spacer(modifier = Modifier.width(2.dp))
            Text("%.1f".format(rating), color = HomeGreen, fontSize = 11.sp, fontWeight = FontWeight.Black)
        }
    }
}
