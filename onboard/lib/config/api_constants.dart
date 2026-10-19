class ApiConstants {
  const ApiConstants._();

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://swado.online/api',
  );

  static const login = '/login';
  static const logout = '/logout';
  static const currentUser = '/user';

  static const onboardingMeta = '/onboarding/meta';
  static const onboardingDashboard = '/onboarding/dashboard';
  static const onboardingPipeline = '/onboarding/pipeline';
  static const onboardingRestaurants = '/onboarding/restaurants';
  static const onboardingDrivers = '/onboarding/drivers';

  static String normalizeBaseUrl(String value) =>
      value.trim().replaceAll(RegExp(r'/+$'), '');
}
