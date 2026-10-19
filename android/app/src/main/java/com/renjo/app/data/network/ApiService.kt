package com.renjo.app.data.network

import com.renjo.app.data.network.dto.AddressListEnvelope
import com.renjo.app.data.network.dto.AddressMutationEnvelope
import com.renjo.app.data.network.dto.AddressRequest
import com.renjo.app.data.network.dto.AuthEnvelope
import com.renjo.app.data.network.dto.BrandingEnvelope
import com.renjo.app.data.network.dto.LoginRequest
import com.renjo.app.data.network.dto.LoginWithPhoneRequest
import com.renjo.app.data.network.dto.PhoneStatusEnvelope
import com.renjo.app.data.network.dto.RegisterRequest
import com.renjo.app.data.network.dto.ResetPasswordByPhoneRequest
import com.renjo.app.data.network.dto.BannerListEnvelope
import com.renjo.app.data.network.dto.BookTableRequest
import com.renjo.app.data.network.dto.BookingReviewRequest
import com.renjo.app.data.network.dto.CancelBookingRequest
import com.renjo.app.data.network.dto.CelebrationTypeEnvelope
import com.renjo.app.data.network.dto.DiningBookingEnvelope
import com.renjo.app.data.network.dto.DiningBookingListEnvelope
import com.renjo.app.data.network.dto.DiningRestaurantListEnvelope
import com.renjo.app.data.network.dto.HomeSectionEnvelope
import com.renjo.app.data.network.dto.MenuResponseEnvelope
import com.renjo.app.data.network.dto.CancelOrderRequest
import com.renjo.app.data.network.dto.CancelPaymentRequest
import com.renjo.app.data.network.dto.OfferListEnvelope
import com.renjo.app.data.network.dto.OrderDetailEnvelope
import com.renjo.app.data.network.dto.OrderFeedbackRequest
import com.renjo.app.data.network.dto.OrderListEnvelope
import com.renjo.app.data.network.dto.OrderSummaryEnvelope
import com.renjo.app.data.network.dto.OrderSummaryRequest
import com.renjo.app.data.network.dto.OrderTipRequest
import com.renjo.app.data.network.dto.OrderTrackEnvelope
import com.renjo.app.data.network.dto.PayOrderEnvelope
import com.renjo.app.data.network.dto.PayOrderRequest
import com.renjo.app.data.network.dto.PaymentStatusEnvelope
import com.renjo.app.data.network.dto.PlaceOrderEnvelope
import com.renjo.app.data.network.dto.PlaceOrderRequest
import com.renjo.app.data.network.dto.RestaurantListEnvelope
import com.renjo.app.data.network.dto.SendOtpEnvelope
import com.renjo.app.data.network.dto.SendOtpRequest
import com.renjo.app.data.network.dto.SimpleEnvelope
import com.renjo.app.data.network.dto.SocialLoginRequest
import com.renjo.app.data.network.dto.UserEnvelope
import com.renjo.app.data.network.dto.VerifyFirebasePhoneRequest
import com.renjo.app.data.network.dto.VerifyOtpEnvelope
import com.renjo.app.data.network.dto.VerifyOtpRequest
import com.renjo.app.data.network.dto.VerifyPaymentRequest
import com.renjo.app.data.network.dto.WalletEnvelope
import com.renjo.app.data.network.dto.WalletTopUpEnvelope
import com.renjo.app.data.network.dto.WalletTopUpRequest
import com.renjo.app.data.network.dto.WalletTopUpVerifyRequest
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

/**
 * Grows one method per phase as endpoints from the API contract doc
 * (d:/karn/Swado/docs/api-contract/) get wired up.
 */
interface ApiService {
    @GET("app/branding")
    suspend fun getBranding(): BrandingEnvelope

    @GET("auth/phone/status")
    suspend fun phoneStatus(@Query("phone") phone: String, @Query("role") role: String? = null): PhoneStatusEnvelope

    @POST("auth/otp/send")
    suspend fun sendOtp(@Body request: SendOtpRequest): SendOtpEnvelope

    @POST("auth/otp/verify")
    suspend fun verifyOtp(@Body request: VerifyOtpRequest): VerifyOtpEnvelope

    @POST("login")
    suspend fun login(@Body request: LoginRequest): AuthEnvelope

    @POST("register")
    suspend fun register(@Body request: RegisterRequest): AuthEnvelope

    @POST("forgot-password/reset-by-phone")
    suspend fun resetPasswordByPhone(@Body request: ResetPasswordByPhoneRequest): SimpleEnvelope

    @POST("logout")
    suspend fun logout(): SimpleEnvelope

    @GET("user")
    suspend fun getCurrentUser(): UserEnvelope

    @GET("restaurants/nearby")
    suspend fun nearbyRestaurants(
        @Query("lat") lat: Double,
        @Query("lng") lng: Double,
        @Query("radius") radius: Double? = null,
        @Query("query") query: String? = null,
    ): RestaurantListEnvelope

    @GET("home/sections")
    suspend fun homeSections(
        @Query("lat") lat: Double? = null,
        @Query("lng") lng: Double? = null,
        @Query("radius") radius: Double? = null,
    ): HomeSectionEnvelope

    @GET("banners")
    suspend fun banners(): BannerListEnvelope

    @GET("offers/active")
    suspend fun activeOffers(): OfferListEnvelope

    @GET("restaurants/{restaurantId}/menu")
    suspend fun menu(@Path("restaurantId") restaurantId: Int): MenuResponseEnvelope

    @GET("addresses")
    suspend fun addresses(@Query("restaurant_id") restaurantId: Int? = null): AddressListEnvelope

    @POST("addresses")
    suspend fun addAddress(@Body request: AddressRequest): AddressMutationEnvelope

    @PUT("addresses/{id}")
    suspend fun updateAddress(@Path("id") id: Int, @Body request: AddressRequest): AddressMutationEnvelope

    @DELETE("addresses/{id}")
    suspend fun deleteAddress(@Path("id") id: Int): SimpleEnvelope

    @POST("addresses/default/{id}")
    suspend fun setDefaultAddress(@Path("id") id: Int): SimpleEnvelope

    @POST("orders/summary")
    suspend fun orderSummary(@Body request: OrderSummaryRequest): OrderSummaryEnvelope

    @POST("orders")
    suspend fun placeOrder(@Body request: PlaceOrderRequest): PlaceOrderEnvelope

    @POST("orders/{id}/pay")
    suspend fun payOrder(@Path("id") orderId: Int, @Body request: PayOrderRequest): PayOrderEnvelope

    @GET("orders/{id}/payment-status")
    suspend fun paymentStatus(@Path("id") orderId: Int): PaymentStatusEnvelope

    @POST("payments/verify")
    suspend fun verifyPayment(@Body request: VerifyPaymentRequest): SimpleEnvelope

    @POST("payments/cancel")
    suspend fun cancelPayment(@Body request: CancelPaymentRequest): SimpleEnvelope

    @GET("orders")
    suspend fun myOrders(@Query("page") page: Int? = null): OrderListEnvelope

    @GET("orders/{id}")
    suspend fun orderDetail(@Path("id") orderId: Int): OrderDetailEnvelope

    @GET("orders/{id}/track")
    suspend fun trackOrder(@Path("id") orderId: Int): OrderTrackEnvelope

    @POST("orders/{id}/cancel")
    suspend fun cancelOrder(@Path("id") orderId: Int, @Body request: CancelOrderRequest): SimpleEnvelope

    @POST("orders/{id}/feedback")
    suspend fun submitOrderFeedback(@Path("id") orderId: Int, @Body request: OrderFeedbackRequest): SimpleEnvelope

    @POST("orders/{id}/tip")
    suspend fun tipOrder(@Path("id") orderId: Int, @Body request: OrderTipRequest): SimpleEnvelope

    @GET("wallet")
    suspend fun wallet(): WalletEnvelope

    @POST("wallet/top-up")
    suspend fun walletTopUp(@Body request: WalletTopUpRequest): WalletTopUpEnvelope

    @POST("wallet/top-up/verify")
    suspend fun walletTopUpVerify(@Body request: WalletTopUpVerifyRequest): WalletEnvelope

    @GET("dining/celebration-types")
    suspend fun celebrationTypes(): CelebrationTypeEnvelope

    @GET("dining/restaurants")
    suspend fun diningRestaurants(
        @Query("lat") lat: Double,
        @Query("lng") lng: Double,
        @Query("radius") radius: Double? = null,
    ): DiningRestaurantListEnvelope

    @POST("dining/book")
    suspend fun bookTable(@Body request: BookTableRequest): DiningBookingEnvelope

    @GET("dining/my-bookings")
    suspend fun myBookings(): DiningBookingListEnvelope

    @GET("dining/bookings/{id}")
    suspend fun bookingDetails(@Path("id") id: Int): DiningBookingEnvelope

    @POST("dining/cancel/{id}")
    suspend fun cancelBooking(@Path("id") id: Int, @Body request: CancelBookingRequest): SimpleEnvelope

    @POST("dining/bookings/{id}/review")
    suspend fun submitBookingReview(@Path("id") id: Int, @Body request: BookingReviewRequest): SimpleEnvelope

    // Firebase-dependent paths — structurally complete, need the user's real
    // Firebase project (google-services.json) wired up before they're reachable.
    @POST("auth/social-login")
    suspend fun socialLogin(@Body request: SocialLoginRequest): AuthEnvelope

    @POST("auth/login-with-phone")
    suspend fun loginWithPhone(@Body request: LoginWithPhoneRequest): AuthEnvelope

    @POST("auth/phone/verify-firebase")
    suspend fun verifyFirebasePhone(@Body request: VerifyFirebasePhoneRequest): VerifyOtpEnvelope
}
