package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

@Serializable
data class WalletEnvelope(
    val success: Boolean,
    val data: WalletPayloadDto? = null,
)

@Serializable
data class WalletPayloadDto(
    val wallet: WalletDto? = null,
    val minimumDriverBalance: Double? = null,
    val transactions: List<WalletTransactionDto> = emptyList(),
)

@Serializable
data class WalletDto(
    val id: Int,
    val balance: Double = 0.0,
    val lockedBalance: Double = 0.0,
    val currency: String? = null,
    val isActive: Boolean = true,
)

@Serializable
data class WalletTransactionDto(
    val id: Int,
    val type: String,
    val amount: Double,
    val balanceAfter: Double? = null,
    val description: String? = null,
    val createdAt: String? = null,
)

@Serializable
data class WalletTopUpRequest(
    val amount: Double,
    val paymentMethod: String,
    val gateway: String? = null,
)

@Serializable
data class WalletTopUpEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: PaymentSessionDto? = null,
)

@Serializable
data class WalletTopUpVerifyRequest(
    val walletRechargeId: Int,
    val paymentId: String,
    val paymentMethod: String,
    val razorpayOrderId: String? = null,
    val razorpaySignature: String? = null,
    val stripePaymentIntentId: String? = null,
)

@Serializable
data class GiftCardRedeemRequest(val code: String)
