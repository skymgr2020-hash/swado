import Foundation

/// Shape captured live from GET /api/app/branding against the real Laravel
/// backend (admin/app/Http/Controllers/Api/AuthController.php::branding), not
/// inferred from source alone. JSONDecoder.keyDecodingStrategy = .convertFromSnakeCase
/// (set in APIClient) maps snake_case wire fields to these camelCase properties.
struct BrandingEnvelope: Decodable {
    let success: Bool
    let data: Branding
}

struct Branding: Decodable {
    let appName: String
    let appLogo: String?
    let appIcon: String?
    let appFavicon: String?
    let frontendBackgroundImage: String?
    let primaryColor: String?
    let secondaryColor: String?
    let restaurantPrimaryColor: String?
    let restaurantSecondaryColor: String?
    let driverPrimaryColor: String?
    let driverSecondaryColor: String?
    let customerPlayStoreUrl: String?
    let customerDeeplinkScheme: String?
    let customerDeeplinkBaseUrl: String?
    let customerOrderDeeplinkTemplate: String?
    let customerRestaurantDeeplinkTemplate: String?
    let customerWalletDeeplinkTemplate: String?
    let vendorMode: String?
    let singleRestaurantId: Int?
    let singleRestaurantName: String?
    let appReleases: [String: AppRelease]?
    let supportEmail: String?
    let supportPhone: String?
    let defaultMobileCountryCode: String?
    let otpServiceProvider: String?
    let codEnabled: Bool?
    let socialLogin: SocialLogin?
    let pusherAppKey: String?
    let pusherAppCluster: String?
    let headerBrandingType: String?
    let onboardingIntroTitle: String?
    let onboardingIntroSubtitle: String?
    let onboardingSlides: [OnboardingSlide]?
}

struct AppRelease: Decodable {
    let latestVersion: String?
    let latestBuildNumber: Int?
    let minSupportedBuildNumber: Int?
    let forceUpdate: Bool?
    let androidUpdateUrl: String?
    let iosUpdateUrl: String?
    let releaseNotes: String?
}

struct SocialLogin: Decodable {
    let enabled: Bool?
    let autoRegister: Bool?
    let autoLinkVerifiedEmail: Bool?
    let providers: SocialProviders?
}

struct SocialProviders: Decodable {
    let google: SocialProvider?
    let apple: SocialProvider?
}

struct SocialProvider: Decodable {
    let enabled: Bool?
    let webClientId: String?
    let servicesId: String?
}

struct OnboardingSlide: Decodable {
    let title: String
    let description: String
    let image: String
}
