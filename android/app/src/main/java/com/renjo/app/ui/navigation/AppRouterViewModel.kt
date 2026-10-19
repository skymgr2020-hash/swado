package com.renjo.app.ui.navigation

import androidx.lifecycle.ViewModel
import com.renjo.app.data.local.AppPreferences
import com.renjo.app.data.session.SessionManager
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

@HiltViewModel
class AppRouterViewModel @Inject constructor(
    private val sessionManager: SessionManager,
    private val appPreferences: AppPreferences,
) : ViewModel() {

    /** Called once /app/branding resolves on the splash screen. */
    suspend fun resolveStartDestination(): String = when {
        sessionManager.isLoggedIn -> Destinations.HOME
        !appPreferences.hasSeenOnboarding() -> Destinations.ONBOARDING
        else -> Destinations.LOGIN
    }
}
