package com.renjo.app.ui.screens.checkout

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CreditCard
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.SheetState
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.renjo.app.ui.theme.CardBorder
import com.renjo.app.ui.theme.Faint
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentMethodSheet(
    sheetState: SheetState,
    options: List<PaymentOption>,
    selectedKey: String,
    onSelect: (String) -> Unit,
    onDismiss: () -> Unit,
) {
    ModalBottomSheet(onDismissRequest = onDismiss, sheetState = sheetState, containerColor = Color(0xFFF5F6FB)) {
        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp).padding(bottom = 24.dp)) {
            Text("Choose payment", color = Ink, fontSize = 18.sp, fontWeight = FontWeight.Black)
            Text(
                "Secure payment powered by Swado",
                color = Muted,
                fontSize = 12.sp,
                fontWeight = FontWeight.Medium,
                modifier = Modifier.padding(top = 2.dp, bottom = 16.dp),
            )
            options.forEach { option ->
                PaymentTile(option = option, selected = option.key == selectedKey, onClick = { if (option.enabled) onSelect(option.key) })
                Spacer(modifier = Modifier.height(8.dp))
            }
        }
    }
}

@Composable
private fun PaymentTile(option: PaymentOption, selected: Boolean, onClick: () -> Unit) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(18.dp))
            .background(Color.White)
            .border(
                if (selected) 1.5.dp else 1.dp,
                if (selected) MaterialTheme.colorScheme.primary.copy(alpha = 0.42f) else CardBorder,
                RoundedCornerShape(18.dp),
            )
            .clickable(enabled = option.enabled, onClick = onClick)
            .padding(vertical = 14.dp, horizontal = 14.dp)
            .alpha(if (option.enabled) 1f else 0.5f),
    ) {
        androidx.compose.foundation.layout.Box(
            modifier = Modifier
                .size(44.dp)
                .background(Color.White, RoundedCornerShape(8.dp))
                .border(
                    1.dp,
                    if (selected) MaterialTheme.colorScheme.primary.copy(alpha = 0.35f) else CardBorder,
                    RoundedCornerShape(8.dp),
                ),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                imageVector = if (option.key == "cod") Icons.Filled.Payments else Icons.Filled.CreditCard,
                contentDescription = null,
                tint = Muted,
                modifier = Modifier.size(20.dp),
            )
        }
        Column(modifier = Modifier.weight(1f).padding(start = 12.dp)) {
            Text(option.title, color = if (option.enabled) Ink else Muted, fontSize = 15.sp, fontWeight = FontWeight.Black)
            Text(option.subtitle, color = if (option.enabled) Muted else Faint, fontSize = 12.5.sp, fontWeight = FontWeight.Medium)
        }
        if (selected) {
            Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(20.dp))
        } else {
            Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = Faint, modifier = Modifier.size(20.dp))
        }
    }
}
