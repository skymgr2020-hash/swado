package com.renjo.app.ui.navigation

import android.net.Uri

object Destinations {
    const val SPLASH = "splash"
    const val ONBOARDING = "onboarding"
    const val LOGIN = "login"
    const val HOME = "home"

    const val PHONE_ENTRY = "phone_entry/{flow}"
    fun phoneEntry(flow: String) = "phone_entry/$flow"

    const val OTP_VERIFY = "otp_verify/{phone}/{flow}"
    fun otpVerify(phone: String, flow: String) = "otp_verify/${Uri.encode(phone)}/$flow"

    const val REGISTER_DETAILS = "register_details/{phone}/{token}"
    fun registerDetails(phone: String, token: String) =
        "register_details/${Uri.encode(phone)}/${Uri.encode(token)}"

    const val RESET_PASSWORD = "reset_password/{phone}/{token}"
    fun resetPassword(phone: String, token: String) =
        "reset_password/${Uri.encode(phone)}/${Uri.encode(token)}"

    const val RESTAURANT_DETAIL = "restaurant_detail/{restaurantId}"
    fun restaurantDetail(restaurantId: Int) = "restaurant_detail/$restaurantId"

    const val CART = "cart"
    const val CHECKOUT = "checkout"
    const val ADDRESS_LIST = "address_list"
    const val ADD_ADDRESS = "add_address"
    const val ORDER_CONFIRMATION = "order_confirmation/{orderId}"
    fun orderConfirmation(orderId: Int) = "order_confirmation/$orderId"

    const val ORDER_DETAIL = "order_detail/{orderId}"
    fun orderDetail(orderId: Int) = "order_detail/$orderId"

    const val WALLET = "wallet"

    const val DINING_LIST = "dining_list"
    const val BOOK_TABLE = "book_table"

    const val OFFERS = "offers"
}

/** Auth flow identifiers shared with the backend's `flow` request param. */
object AuthFlow {
    const val LOGIN = "login"
    const val SIGNUP = "signup"
    const val FORGOT_PASSWORD = "forgot_password"
}
