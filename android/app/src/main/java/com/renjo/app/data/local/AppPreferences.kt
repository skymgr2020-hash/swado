package com.renjo.app.data.local

import android.content.Context
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.first
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore by preferencesDataStore(name = "swado_prefs")

@Singleton
class AppPreferences @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val onboardingSeenKey = booleanPreferencesKey("onboarding_seen")

    suspend fun hasSeenOnboarding(): Boolean =
        context.dataStore.data.first()[onboardingSeenKey] ?: false

    suspend fun setOnboardingSeen() {
        context.dataStore.edit { it[onboardingSeenKey] = true }
    }
}
