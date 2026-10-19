package com.renjo.app.data.network.dto

import kotlinx.serialization.Serializable

/**
 * Two backend flows exist (see docs/api-contract research): Flow A pays an
 * order that already exists (POST /orders/{id}/pay), Flow B is a
 * checkout-token pay-before-order-exists path (POST /payments/checkout/create).
 * Our checkout screen always creates the order first via POST /orders, so
 * only Flow A + verify/cancel/status are wired here. Field names vary
 * per-gateway and even collide (Razorpay's Flow-A `amount` is rupees, but
 * Flow-B's `amount` key gets overwritten by a float total server-side too) —
 * every field is nullable so a per-gateway reader can pick the right one
 * defensively rather than trust a single shape.
 */

@Serializable
data class PayOrderRequest(val gateway: String? = null)

@Serializable
data class PayOrderEnvelope(
    val success: Boolean,
    val message: String? = null,
    val data: PaymentSessionDto? = null,
)

@Serializable
data class PaymentSessionDto(
    val gateway: String? = null,
    val paymentAttemptId: Int? = null,
    val amount: Double? = null,
    val amountMinor: Long? = null,
    val currency: String? = null,
    // Razorpay
    val key: String? = null,
    val orderId: String? = null,
    // Stripe
    val clientSecret: String? = null,
    val publishableKey: String? = null,
    // Cashfree
    val paymentSessionId: String? = null,
    val environment: String? = null,
    // Wallet top-up / dining booking payment reuse this same shape with an
    // extra id identifying the pending transaction to verify against.
    val walletRechargeId: Int? = null,
)

@Serializable
data class VerifyPaymentRequest(
    val orderId: Int,
    val paymentId: String,
    val paymentMethod: String,
    val gateway: String,
    val paymentGateway: String,
    val paymentGatewayProvider: String,
    val razorpayOrderId: String? = null,
    val razorpaySignature: String? = null,
    val stripePaymentIntentId: String? = null,
)

@Serializable
data class CancelPaymentRequest(
    val orderId: Int,
    val reason: String? = null,
)

@Serializable
data class PaymentStatusEnvelope(
    val success: Boolean,
    val data: PaymentStatusDto? = null,
)

@Serializable
data class PaymentStatusDto(
    val paymentStatus: String? = null,
    val paymentMethod: String? = null,
    val transactionId: String? = null,
    val paidAt: String? = null,
)
