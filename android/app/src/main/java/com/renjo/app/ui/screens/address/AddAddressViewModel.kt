package com.renjo.app.ui.screens.address

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.location.LocationProvider
import com.renjo.app.data.network.NetworkResult
import com.renjo.app.data.network.dto.AddressRequest
import com.renjo.app.data.repository.AddressRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

/**
 * Form-based address entry - the Flutter app uses an interactive map
 * pin-drop (map_picker_screen.dart); that's deferred here in favor of using
 * the device's current GPS fix as the coordinates, with the rest entered as plain text. Full
 * map-based picking is a reasonable follow-up, not required for a working
 * address-to-checkout path.
 */
@HiltViewModel
class AddAddressViewModel @Inject constructor(
    private val addressRepository: AddressRepository,
    private val locationProvider: LocationProvider,
) : ViewModel() {

    private val _saving = MutableStateFlow(false)
    val saving: StateFlow<Boolean> = _saving.asStateFlow()

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()

    fun save(
        label: String,
        addressLine: String,
        city: String,
        state: String,
        pincode: String,
        phone: String,
        onSaved: () -> Unit,
    ) {
        if (addressLine.isBlank() || city.isBlank() || pincode.isBlank() || phone.isBlank()) {
            _error.value = "Fill in address, city, pincode, and phone."
            return
        }
        _saving.value = true
        viewModelScope.launch {
            val location = locationProvider.getLastKnownLocation()
            if (location == null) {
                _saving.value = false
                _error.value = "Choose a delivery location before saving this address."
                return@launch
            }
            val result = addressRepository.add(
                AddressRequest(
                    name = label.ifBlank { null },
                    address = addressLine,
                    city = city,
                    state = state,
                    pincode = pincode,
                    phone = phone,
                    latitude = location.lat,
                    longitude = location.lng,
                ),
            )
            _saving.value = false
            when (result) {
                is NetworkResult.Success -> onSaved()
                is NetworkResult.Error -> _error.value = result.message
            }
        }
    }
}
