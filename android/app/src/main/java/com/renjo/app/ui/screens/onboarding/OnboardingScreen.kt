package com.renjo.app.ui.screens.onboarding

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage

@Composable
fun OnboardingScreen(
    viewModel: OnboardingViewModel = hiltViewModel(),
    onFinished: () -> Unit,
) {
    val branding by viewModel.branding.collectAsState()
    val slides = branding?.onboardingSlides.orEmpty()

    Surface(modifier = Modifier.fillMaxSize()) {
        Column(modifier = Modifier.fillMaxSize()) {
            if (slides.isEmpty()) {
                Column(
                    modifier = Modifier.weight(1f).fillMaxWidth().padding(24.dp),
                    verticalArrangement = Arrangement.Center,
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    Text(
                        text = branding?.onboardingIntroTitle ?: "Welcome",
                        style = MaterialTheme.typography.headlineMedium,
                    )
                    branding?.onboardingIntroSubtitle?.let {
                        Text(text = it, style = MaterialTheme.typography.bodyMedium)
                    }
                }
            } else {
                val pagerState = rememberPagerState(pageCount = { slides.size })
                HorizontalPager(state = pagerState, modifier = Modifier.weight(1f)) { page ->
                    val slide = slides[page]
                    Column(
                        modifier = Modifier.fillMaxSize().padding(24.dp),
                        verticalArrangement = Arrangement.Center,
                        horizontalAlignment = Alignment.CenterHorizontally,
                    ) {
                        AsyncImage(
                            model = slide.image,
                            contentDescription = null,
                            modifier = Modifier.fillMaxWidth().weight(1f),
                        )
                        Text(
                            text = slide.title,
                            style = MaterialTheme.typography.titleLarge,
                            textAlign = TextAlign.Center,
                            modifier = Modifier.padding(top = 16.dp),
                        )
                        Text(
                            text = slide.description,
                            style = MaterialTheme.typography.bodyMedium,
                            textAlign = TextAlign.Center,
                            modifier = Modifier.padding(top = 8.dp),
                        )
                    }
                }
            }

            Button(
                onClick = { viewModel.markSeen(onFinished) },
                modifier = Modifier.fillMaxWidth().padding(24.dp),
            ) {
                Text("Get started")
            }
        }
    }
}
