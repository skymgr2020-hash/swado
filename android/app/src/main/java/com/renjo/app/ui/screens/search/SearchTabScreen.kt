package com.renjo.app.ui.screens.search

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.renjo.app.ui.theme.Canvas
import com.renjo.app.ui.theme.Muted

/** Stub for Phase 2 — real search (suggestions/trending/history) is a later phase. */
@Composable
fun SearchTabScreen() {
    Surface(modifier = Modifier.fillMaxSize(), color = Canvas) {
        Column(
            modifier = Modifier.fillMaxSize().padding(24.dp),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Text("Search", style = MaterialTheme.typography.headlineSmall)
            Text("Coming soon", color = Muted)
        }
    }
}
