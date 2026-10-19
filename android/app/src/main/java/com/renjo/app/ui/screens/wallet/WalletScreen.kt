package com.renjo.app.ui.screens.wallet

import android.app.Activity
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.NorthEast
import androidx.compose.material.icons.filled.SouthWest
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.renjo.app.data.network.dto.WalletTransactionDto
import com.renjo.app.ui.theme.CardBorder
import com.renjo.app.ui.theme.Danger
import com.renjo.app.ui.theme.Ink
import com.renjo.app.ui.theme.Muted
import com.renjo.app.ui.theme.WalletCreditBg
import com.renjo.app.ui.theme.WalletCreditFg
import com.renjo.app.ui.theme.WalletDebitBg

@Composable
fun WalletScreen(
    viewModel: WalletViewModel = hiltViewModel(),
    onBack: () -> Unit,
) {
    val uiState by viewModel.uiState.collectAsState()
    val topUpState by viewModel.topUpState.collectAsState()
    var amountText by remember { mutableStateOf("100") }
    val context = LocalContext.current
    val activity = context as? Activity

    LaunchedEffect(topUpState) {
        val state = topUpState
        if (state is TopUpState.RequiresPayment && activity != null) {
            viewModel.launchGateway(activity, state.session)
        }
    }

    Surface(modifier = Modifier.fillMaxSize(), color = Color(0xFFFAFAFA)) {
        Column(modifier = Modifier.fillMaxSize()) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(8.dp)) {
                IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Ink) }
                Text("Wallet", color = Ink, fontSize = 20.sp, fontWeight = FontWeight.Black)
            }

            when (val state = uiState) {
                is WalletUiState.Loading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                is WalletUiState.Error -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Danger)
                }
                is WalletUiState.Loaded -> {
                    Column(modifier = Modifier.padding(16.dp)) {
                        val primary = MaterialTheme.colorScheme.primary
                        val secondary = MaterialTheme.colorScheme.secondary
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(
                                    Brush.linearGradient(listOf(primary, secondary), start = Offset(0f, 0f)),
                                    RoundedCornerShape(26.dp),
                                )
                                .padding(20.dp),
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(34.dp)
                                        .background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(11.dp)),
                                    contentAlignment = Alignment.Center,
                                ) {
                                    Icon(Icons.Filled.AccountBalanceWallet, contentDescription = null, tint = Color.White, modifier = Modifier.size(18.dp))
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Text("Wallet Balance", color = Color.White.copy(alpha = 0.88f), fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                            Spacer(modifier = Modifier.height(10.dp))
                            Text(
                                "₹${"%.2f".format(state.wallet.wallet?.balance ?: 0.0)}",
                                color = Color.White,
                                fontSize = 36.sp,
                                fontWeight = FontWeight.Black,
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                "Usable across Swado for one-tap payments",
                                color = Color.White.copy(alpha = 0.78f),
                                fontSize = 12.sp,
                                fontWeight = FontWeight.SemiBold,
                            )
                        }
                        Spacer(modifier = Modifier.height(16.dp))
                        Row {
                            OutlinedTextField(
                                value = amountText,
                                onValueChange = { amountText = it.filter { c -> c.isDigit() } },
                                label = { Text("Amount") },
                                modifier = Modifier.weight(1f),
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Button(
                                onClick = { amountText.toDoubleOrNull()?.let { viewModel.startTopUp(it, "razorpay") } },
                                enabled = topUpState !is TopUpState.Starting && topUpState !is TopUpState.Verifying,
                                colors = ButtonDefaults.buttonColors(containerColor = primary),
                            ) {
                                if (topUpState is TopUpState.Starting || topUpState is TopUpState.Verifying) {
                                    CircularProgressIndicator(modifier = Modifier.height(18.dp), color = Color.White)
                                } else {
                                    Text("Add Money")
                                }
                            }
                        }
                        if (topUpState is TopUpState.Error) {
                            Text((topUpState as TopUpState.Error).message, color = Danger, fontSize = 12.sp, modifier = Modifier.padding(top = 8.dp))
                        }
                    }
                    Text(
                        "PASSBOOK",
                        color = Muted,
                        fontWeight = FontWeight.Black,
                        fontSize = 14.sp,
                        letterSpacing = 0.5.sp,
                        modifier = Modifier.padding(horizontal = 16.dp),
                    )
                    if (state.wallet.transactions.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("Your transactions will appear here", color = Muted, fontSize = 13.sp)
                        }
                    } else {
                        LazyColumn(contentPadding = PaddingValues(16.dp)) {
                            items(state.wallet.transactions, key = { it.id }) { tx ->
                                TransactionRow(tx)
                                Spacer(modifier = Modifier.height(8.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun TransactionRow(tx: WalletTransactionDto) {
    val credit = tx.type != "debit"
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .background(Color.White, RoundedCornerShape(18.dp))
            .border(1.dp, CardBorder, RoundedCornerShape(18.dp))
            .padding(14.dp),
    ) {
        Box(
            modifier = Modifier
                .size(42.dp)
                .background(if (credit) WalletCreditBg else WalletDebitBg, RoundedCornerShape(14.dp)),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                if (credit) Icons.Filled.SouthWest else Icons.Filled.NorthEast,
                contentDescription = null,
                tint = if (credit) WalletCreditFg else MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(18.dp),
            )
        }
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(tx.description ?: tx.type.replaceFirstChar { it.uppercase() }, color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Black)
            Text(tx.type.replaceFirstChar { it.uppercase() }, color = Muted, fontSize = 12.sp, fontWeight = FontWeight.Medium)
        }
        Column(horizontalAlignment = Alignment.End) {
            Text(
                "${if (credit) "+" else "-"}₹${"%.2f".format(tx.amount)}",
                color = if (credit) WalletCreditFg else MaterialTheme.colorScheme.primary,
                fontSize = 14.sp,
                fontWeight = FontWeight.Black,
            )
            tx.createdAt?.let { Text(it, color = Muted, fontSize = 10.5.sp, fontWeight = FontWeight.SemiBold) }
        }
    }
}
