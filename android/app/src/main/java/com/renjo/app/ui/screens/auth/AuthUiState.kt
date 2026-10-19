package com.renjo.app.ui.screens.auth

/** Shared across the auth screens' ViewModels — small enough not to need a per-screen variant. */
sealed interface AuthUiState {
    data object Idle : AuthUiState
    data object Loading : AuthUiState
    data object Success : AuthUiState
    data class Error(val message: String) : AuthUiState
}
