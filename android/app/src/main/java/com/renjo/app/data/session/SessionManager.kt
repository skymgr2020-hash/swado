package com.renjo.app.data.session

import com.renjo.app.data.local.TokenStore
import com.renjo.app.data.network.dto.UserDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Single source of truth for "who is logged in right now", shared across the
 * app. A 401 anywhere (see ApiService callers) should route through
 * [clearSession] to force back to the login screen, matching the backend's
 * "token never expires, only a 401 signals it was revoked/invalid" contract.
 */
@Singleton
class SessionManager @Inject constructor(
    private val tokenStore: TokenStore,
) {
    private val _currentUser = MutableStateFlow<UserDto?>(null)
    val currentUser: StateFlow<UserDto?> = _currentUser.asStateFlow()

    val isLoggedIn: Boolean
        get() = tokenStore.token != null

    fun startSession(token: String, user: UserDto) {
        tokenStore.token = token
        _currentUser.value = user
    }

    fun updateUser(user: UserDto) {
        _currentUser.value = user
    }

    fun clearSession() {
        tokenStore.clear()
        _currentUser.value = null
    }
}
