package com.renjo.app.ui.screens.home

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.renjo.app.data.location.LatLng
import com.renjo.app.data.location.PlaceSuggestion
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

/**
 * Mirrors the Flutter app's location bar tap → picker sheet UX: use current
 * GPS location or search for a place (via the same Nominatim backend the
 * Flutter app uses — see NominatimService).
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LocationPickerSheet(
    onDismiss: () -> Unit,
    onUseCurrentLocation: suspend () -> Unit,
    onSearch: suspend (String) -> List<PlaceSuggestion>,
    onSelect: (PlaceSuggestion) -> Unit,
) {
    var query by remember { mutableStateOf("") }
    var results by remember { mutableStateOf<List<PlaceSuggestion>>(emptyList()) }
    var isSearching by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(query) {
        if (query.isBlank()) {
            results = emptyList()
            return@LaunchedEffect
        }
        isSearching = true
        delay(400) // debounce
        results = onSearch(query)
        isSearching = false
    }

    ModalBottomSheet(onDismissRequest = onDismiss) {
        Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp).padding(bottom = 24.dp)) {
            Text("Choose your location", color = Ink, style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(16.dp))

            OutlinedTextField(
                value = query,
                onValueChange = { query = it },
                placeholder = { Text("Search for area, street name...") },
                leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null) },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
                modifier = Modifier.fillMaxWidth(),
            )
            Spacer(modifier = Modifier.height(12.dp))

            TextButton(onClick = { scope.launch { onUseCurrentLocation() } }) {
                Icon(Icons.Filled.MyLocation, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Use current location", color = MaterialTheme.colorScheme.primary)
            }

            if (isSearching) {
                Row(modifier = Modifier.padding(16.dp), horizontalArrangement = Arrangement.Center) {
                    CircularProgressIndicator(modifier = Modifier.height(20.dp))
                }
            } else if (results.isNotEmpty()) {
                LazyColumn(modifier = Modifier.height(320.dp)) {
                    items(results) { suggestion ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { onSelect(suggestion) }
                                .padding(vertical = 12.dp),
                            verticalAlignment = Alignment.Top,
                        ) {
                            Icon(Icons.Filled.LocationOn, contentDescription = null, tint = Muted)
                            Spacer(modifier = Modifier.width(12.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text(suggestion.cityLabel, color = Ink, style = MaterialTheme.typography.bodyMedium)
                                Text(suggestion.label, color = Muted, style = MaterialTheme.typography.bodySmall, maxLines = 2)
                            }
                        }
                    }
                }
            } else if (query.isNotBlank()) {
                Text("No results found", color = Muted, modifier = Modifier.padding(16.dp))
            }
        }
    }
}
