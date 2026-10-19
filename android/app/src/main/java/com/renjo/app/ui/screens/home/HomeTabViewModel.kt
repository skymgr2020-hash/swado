package com.renjo.app.ui.screens.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.location.LatLng
import com.renjo.app.data.location.LocationProvider
import com.renjo.app.data.location.NominatimService
import com.renjo.app.data.location.PlaceSuggestion
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.BannerDto
import com.renjo.app.data.network.dto.HomeSectionDto
import com.renjo.app.data.network.dto.RestaurantDto
import com.renjo.app.data.repository.RestaurantRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface HomeFeedState {
    data object Loading : HomeFeedState
    data class Loaded(
        val banners: List<BannerDto>,
        val sections: List<HomeSectionDto>,
        val restaurants: List<RestaurantDto>,
        val locationLabel: String,
    ) : HomeFeedState
    data class Error(val message: String) : HomeFeedState
}

/**
 * Mirrors the Flutter home screen's section-driven architecture: fetches the
 * admin-ordered section list from GET /home/sections (Home Section
 * Management in the admin panel) and cross-fetches GET /restaurants/nearby
 * to fill the "restaurant_discovery" section's slot, since that section's
 * `items` are always empty by backend design - see HomeSectionDto's kdoc.
 * The hero banner comes from a separate GET /api/banners call - NOT
 * /home/sections' "banner_carousel" section type, which isn't currently
 * configured on production (see BannerDto kdoc).
 * Location can be overridden via the location picker (search or GPS),
 * matching the Flutter app's location-bar-opens-a-picker UX.
 */
@HiltViewModel
class HomeTabViewModel @Inject constructor(
    private val restaurantRepository: RestaurantRepository,
    private val locationProvider: LocationProvider,
    private val nominatimService: NominatimService,
) : ViewModel() {

    private val _feedState = MutableStateFlow<HomeFeedState>(HomeFeedState.Loading)
    val feedState: StateFlow<HomeFeedState> = _feedState.asStateFlow()

    private val _vegOnly = MutableStateFlow(false)
    val vegOnly: StateFlow<Boolean> = _vegOnly.asStateFlow()

    private var manualLocation: LatLng? = null

    init {
        loadRestaurants()
    }

    fun toggleVegOnly() {
        _vegOnly.value = !_vegOnly.value
    }

    /** Called from the location picker when the user picks a search result or GPS. */
    fun selectLocation(location: LatLng, label: String?) {
        manualLocation = location
        loadRestaurants(presetLabel = label)
    }

    suspend fun searchPlaces(query: String): List<PlaceSuggestion> = nominatimService.search(query)

    suspend fun currentDeviceLocation(): LatLng? = locationProvider.getLastKnownLocation()

    fun loadRestaurants(presetLabel: String? = null) {
        _feedState.value = HomeFeedState.Loading
        viewModelScope.launch {
            val location = manualLocation ?: locationProvider.getLastKnownLocation()
            if (location == null) {
                _feedState.value = HomeFeedState.Error("Choose a delivery location to see restaurants near you.")
                return@launch
            }

            val bannersDeferred = async { restaurantRepository.banners() }
            val sectionsDeferred = async { restaurantRepository.homeSections(location.lat, location.lng) }
            val nearbyDeferred = async { restaurantRepository.nearby(location.lat, location.lng) }
            val labelDeferred = async { presetLabel ?: nominatimService.reverseGeocode(location.lat, location.lng) }

            val nearbyResult = nearbyDeferred.await()
            if (nearbyResult is NetworkResult.Error) {
                _feedState.value = HomeFeedState.Error(nearbyResult.message)
                return@launch
            }
            val restaurants = (nearbyResult as NetworkResult.Success).data

            val banners = (bannersDeferred.await() as? NetworkResult.Success)?.data.orEmpty()
            val sections = (sectionsDeferred.await() as? NetworkResult.Success)?.data.orEmpty()
            val locationLabel = labelDeferred.await() ?: "Nearby"

            _feedState.value = HomeFeedState.Loaded(banners, sections, restaurants, locationLabel)
        }
    }
}
