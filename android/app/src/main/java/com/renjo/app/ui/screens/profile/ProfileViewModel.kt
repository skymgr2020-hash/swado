package com.renjo.app.ui.screens.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.renjo.app.data.network.dto.UserDto
import com.renjo.app.data.repository.AuthRepository
import com.renjo.app.data.session.SessionManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

/** Placeholder Profile tab for Phase 2. Phase 7 replaces this with the real
 * account/wallet/rewards/support hub. */
@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    sessionManager: SessionManager,
) : ViewModel() {

    val currentUser: StateFlow<UserDto?> = sessionManager.currentUser

    init {
        // Refresh from /user in case the cached session payload (from
        // login/register/otp) is stale.
        viewModelScope.launch { authRepository.refreshCurrentUser() }
    }

    fun logout(onLoggedOut: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onLoggedOut()
        }
    }
}
