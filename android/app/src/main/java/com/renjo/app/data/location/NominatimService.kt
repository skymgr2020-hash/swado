package com.renjo.app.data.location

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import okhttp3.OkHttpClient
import okhttp3.Request
import java.net.URLEncoder
import javax.inject.Inject
import javax.inject.Singleton

@Serializable
data class NominatimAddress(
    val city: String? = null,
    val town: String? = null,
    val village: String? = null,
    val municipality: String? = null,
    val district: String? = null,
    val county: String? = null,
    val state: String? = null,
)

@Serializable
data class NominatimResult(
    val lat: String,
    val lon: String,
    val displayName: String? = null,
    val address: NominatimAddress? = null,
)

data class PlaceSuggestion(val label: String, val cityLabel: String, val location: LatLng)

/**
 * Mirrors the Flutter app's LocationService: OpenStreetMap Nominatim for
 * forward/reverse geocoding (deliberately not Google Geocoding — no separate
 * API key needed, same choice the Flutter app made). Nominatim's usage
 * policy requires a real User-Agent identifying the app, not the default
 * OkHttp one.
 */
@Singleton
class NominatimService @Inject constructor() {
    private val client = OkHttpClient()
    private val json = Json { ignoreUnknownKeys = true; coerceInputValues = true }
    private val userAgent = "SwadoAndroidNative/1.0 (+https://swado.online)"

    suspend fun reverseGeocode(lat: Double, lng: Double): String? = withContext(Dispatchers.IO) {
        val url = "https://nominatim.openstreetmap.org/reverse?lat=$lat&lon=$lng&format=json&addressdetails=1&accept-language=en"
        val result = runCatching { fetch<NominatimResult>(url) }.getOrNull() ?: return@withContext null
        cityLabel(result)
    }

    suspend fun search(query: String): List<PlaceSuggestion> = withContext(Dispatchers.IO) {
        if (query.isBlank()) return@withContext emptyList()
        val encoded = URLEncoder.encode(query, "UTF-8")
        val url = "https://nominatim.openstreetmap.org/search?q=$encoded&format=json&addressdetails=1&limit=8&accept-language=en"
        val results = runCatching { fetchList<NominatimResult>(url) }.getOrNull().orEmpty()
        results.mapNotNull { result ->
            val lat = result.lat.toDoubleOrNull() ?: return@mapNotNull null
            val lon = result.lon.toDoubleOrNull() ?: return@mapNotNull null
            PlaceSuggestion(
                label = result.displayName ?: cityLabel(result) ?: query,
                cityLabel = cityLabel(result) ?: query,
                location = LatLng(lat, lon),
            )
        }
    }

    private fun cityLabel(result: NominatimResult): String? {
        val city = result.address?.city ?: result.address?.town ?: result.address?.village
            ?: result.address?.municipality ?: result.address?.district ?: result.address?.county
            ?: result.address?.state
        if (!city.isNullOrBlank()) return city
        val displayName = result.displayName ?: return null
        val parts = displayName.split(",").map { it.trim() }
        return if (parts.size > 1) parts[parts.size - 2] else parts.firstOrNull()
    }

    private inline fun <reified T> fetch(url: String): T? {
        val request = Request.Builder().url(url).header("User-Agent", userAgent).header("Accept-Language", "en").build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) return null
            val body = response.body?.string() ?: return null
            return json.decodeFromString(body)
        }
    }

    private inline fun <reified T> fetchList(url: String): List<T>? {
        val request = Request.Builder().url(url).header("User-Agent", userAgent).header("Accept-Language", "en").build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) return null
            val body = response.body?.string() ?: return null
            return json.decodeFromString(body)
        }
    }
}
