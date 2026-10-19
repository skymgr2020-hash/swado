package com.renjo.app.ui.navigation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.navigation.navDeepLink
import androidx.compose.ui.graphics.Color
import com.renjo.app.data.network.dto.BrandingDto
import com.renjo.app.ui.screens.auth.LoginScreen
import com.renjo.app.ui.screens.auth.OtpVerifyResult
import com.renjo.app.ui.screens.auth.OtpVerifyScreen
import com.renjo.app.ui.screens.auth.PhoneEntryScreen
import com.renjo.app.ui.screens.auth.RegisterDetailsScreen
import com.renjo.app.ui.screens.auth.ResetPasswordScreen
import com.renjo.app.ui.screens.address.AddAddressScreen
import com.renjo.app.ui.screens.cart.CartScreen
import com.renjo.app.ui.screens.checkout.CheckoutScreen
import com.renjo.app.ui.screens.dining.BookTableScreen
import com.renjo.app.ui.screens.dining.DiningListScreen
import com.renjo.app.ui.screens.offers.OffersScreen
import com.renjo.app.ui.screens.home.HomeShellScreen
import com.renjo.app.ui.screens.onboarding.OnboardingScreen
import com.renjo.app.ui.screens.order.OrderConfirmationScreen
import com.renjo.app.ui.screens.orders.OrderDetailScreen
import com.renjo.app.ui.screens.restaurant.RestaurantDetailScreen
import com.renjo.app.ui.screens.splash.SplashScreen
import com.renjo.app.ui.screens.wallet.WalletScreen
import com.renjo.app.ui.theme.SwadoTheme
import com.renjo.app.ui.theme.parseHexColorOrNull
import kotlinx.coroutines.launch

@Composable
fun SwadoApp() {
    var brandPrimary by mutableStateOf<Color?>(null)
    var brandSecondary by mutableStateOf<Color?>(null)

    SwadoTheme(brandPrimary = brandPrimary, brandSecondary = brandSecondary) {
        // enableEdgeToEdge() (MainActivity) draws content behind the system
        // bars; safeDrawingPadding() here insets every screen once (applied
        // above the NavHost, not per-screen) so titles/buttons never render
        // under the status/nav bars.
        Box(modifier = Modifier.fillMaxSize().safeDrawingPadding()) {
            val navController = rememberNavController()
            NavGraph(
                navController = navController,
                onBrandColors = { branding ->
                    brandPrimary = parseHexColorOrNull(branding.primaryColor) ?: brandPrimary
                    brandSecondary = parseHexColorOrNull(branding.secondaryColor) ?: brandSecondary
                },
            )
        }
    }
}

@Composable
private fun NavGraph(
    navController: NavHostController,
    onBrandColors: (BrandingDto) -> Unit,
) {
    fun handleAuthOutcome(result: OtpVerifyResult, phone: String) {
        when (result) {
            is OtpVerifyResult.LoggedIn -> navController.navigate(Destinations.HOME) {
                popUpTo(Destinations.LOGIN) { inclusive = true }
            }
            is OtpVerifyResult.ReadyToRegister ->
                navController.navigate(Destinations.registerDetails(phone, result.verifiedPhoneToken))
            is OtpVerifyResult.ReadyToResetPassword ->
                navController.navigate(Destinations.resetPassword(phone, result.verifiedPhoneToken))
        }
    }

    NavHost(navController = navController, startDestination = Destinations.SPLASH) {

        composable(Destinations.SPLASH) {
            val routerViewModel: AppRouterViewModel = hiltViewModel()
            val scope = rememberCoroutineScope()
            SplashScreen(
                onBrandingReady = { branding ->
                    onBrandColors(branding)
                    scope.launch {
                        val destination = routerViewModel.resolveStartDestination()
                        navController.navigate(destination) {
                            popUpTo(Destinations.SPLASH) { inclusive = true }
                        }
                    }
                },
            )
        }

        composable(Destinations.ONBOARDING) {
            OnboardingScreen(
                onFinished = {
                    navController.navigate(Destinations.LOGIN) {
                        popUpTo(Destinations.ONBOARDING) { inclusive = true }
                    }
                },
            )
        }

        composable(Destinations.LOGIN) {
            LoginScreen(
                onOtpRequired = { phone, flow ->
                    navController.navigate(Destinations.otpVerify(phone, flow))
                },
                onCompleted = { result -> handleAuthOutcome(result.outcome, result.phone) },
                onGoogleSignIn = {
                    // Wired up once the Firebase project has a real Web OAuth
                    // client ID configured (web_client_id is currently empty
                    // from /app/branding) — see docs/api-contract/auth.md.
                },
            )
        }

        composable(
            Destinations.PHONE_ENTRY,
            arguments = listOf(navArgument("flow") { type = NavType.StringType }),
        ) { backStackEntry ->
            val flow = backStackEntry.arguments?.getString("flow") ?: AuthFlow.SIGNUP
            PhoneEntryScreen(
                onOtpSent = { phone -> navController.navigate(Destinations.otpVerify(phone, flow)) },
                onCompleted = { result -> handleAuthOutcome(result.outcome, result.phone) },
            )
        }

        composable(
            Destinations.OTP_VERIFY,
            arguments = listOf(
                navArgument("phone") { type = NavType.StringType },
                navArgument("flow") { type = NavType.StringType },
            ),
        ) { backStackEntry ->
            val phone = backStackEntry.arguments?.getString("phone").orEmpty()
            OtpVerifyScreen(
                onBack = { navController.popBackStack() },
                onResult = { result -> handleAuthOutcome(result, phone) },
            )
        }

        composable(
            Destinations.REGISTER_DETAILS,
            arguments = listOf(
                navArgument("phone") { type = NavType.StringType },
                navArgument("token") { type = NavType.StringType },
            ),
        ) {
            RegisterDetailsScreen(
                onRegistered = {
                    navController.navigate(Destinations.HOME) {
                        popUpTo(Destinations.LOGIN) { inclusive = true }
                    }
                },
            )
        }

        composable(
            Destinations.RESET_PASSWORD,
            arguments = listOf(
                navArgument("phone") { type = NavType.StringType },
                navArgument("token") { type = NavType.StringType },
            ),
        ) {
            ResetPasswordScreen(
                onDone = {
                    navController.navigate(Destinations.LOGIN) {
                        popUpTo(Destinations.LOGIN) { inclusive = true }
                    }
                },
            )
        }

        composable(Destinations.HOME) {
            HomeShellScreen(
                onLoggedOut = {
                    navController.navigate(Destinations.LOGIN) {
                        popUpTo(Destinations.HOME) { inclusive = true }
                    }
                },
                onOpenRestaurant = { restaurantId ->
                    navController.navigate(Destinations.restaurantDetail(restaurantId))
                },
                onOpenOrder = { orderId -> navController.navigate(Destinations.orderDetail(orderId)) },
                onOpenWallet = { navController.navigate(Destinations.WALLET) },
                onOpenDining = { navController.navigate(Destinations.DINING_LIST) },
                onOpenOffers = { navController.navigate(Destinations.OFFERS) },
            )
        }

        composable(
            Destinations.ORDER_DETAIL,
            arguments = listOf(navArgument("orderId") { type = NavType.IntType }),
            deepLinks = listOf(navDeepLink { uriPattern = "foodflow://orders/{orderId}" }),
        ) {
            OrderDetailScreen(onBack = { navController.popBackStack() })
        }

        composable(
            Destinations.WALLET,
            deepLinks = listOf(navDeepLink { uriPattern = "foodflow://wallet" }),
        ) {
            WalletScreen(onBack = { navController.popBackStack() })
        }

        composable(Destinations.DINING_LIST) {
            DiningListScreen(
                onBack = { navController.popBackStack() },
                onNewBooking = { navController.navigate(Destinations.BOOK_TABLE) },
            )
        }

        composable(Destinations.BOOK_TABLE) {
            BookTableScreen(
                onBack = { navController.popBackStack() },
                onBooked = { navController.popBackStack() },
            )
        }

        composable(Destinations.OFFERS) {
            OffersScreen(onBack = { navController.popBackStack() })
        }

        composable(
            Destinations.RESTAURANT_DETAIL,
            arguments = listOf(navArgument("restaurantId") { type = NavType.IntType }),
            deepLinks = listOf(navDeepLink { uriPattern = "foodflow://restaurants/{restaurantId}" }),
        ) {
            RestaurantDetailScreen(
                onBack = { navController.popBackStack() },
                onViewCart = { navController.navigate(Destinations.CART) },
            )
        }

        composable(Destinations.CART) {
            CartScreen(
                onBack = { navController.popBackStack() },
                onCheckout = { navController.navigate(Destinations.CHECKOUT) },
            )
        }

        composable(Destinations.CHECKOUT) {
            CheckoutScreen(
                onBack = { navController.popBackStack() },
                onAddAddress = { navController.navigate(Destinations.ADD_ADDRESS) },
                onOrderPlaced = { orderId ->
                    navController.navigate(Destinations.orderConfirmation(orderId)) {
                        popUpTo(Destinations.HOME)
                    }
                },
            )
        }

        composable(Destinations.ADD_ADDRESS) {
            AddAddressScreen(
                onBack = { navController.popBackStack() },
                onSaved = { navController.popBackStack() },
            )
        }

        composable(
            Destinations.ORDER_CONFIRMATION,
            arguments = listOf(navArgument("orderId") { type = NavType.IntType }),
        ) { backStackEntry ->
            val orderId = backStackEntry.arguments?.getInt("orderId") ?: 0
            OrderConfirmationScreen(
                orderId = orderId,
                onDone = {
                    navController.navigate(Destinations.HOME) {
                        popUpTo(Destinations.HOME) { inclusive = true }
                    }
                },
                onTrackOrder = { id ->
                    navController.navigate(Destinations.orderDetail(id)) {
                        popUpTo(Destinations.HOME)
                    }
                },
            )
        }
    }
}
