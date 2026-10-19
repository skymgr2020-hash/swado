// lib/screens/driver/driver_dashboard.dart

import 'dart:async';
import 'dart:math';
import 'dart:ui' show ImageFilter;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../../providers/auth_provider.dart';
import '../../services/location_service.dart';
import '../../services/partner_application_service.dart';
import '../../services/api_service.dart';
import '../../services/foreground_service_manager.dart';
import '../../services/incoming_order_alert_service.dart';
import '../../services/order_alert_permission_manager.dart';
import '../../services/sound_service.dart';
import '../../services/websocket_service.dart';
import '../../config/api_constants.dart';
import '../../theme/foodflow_theme.dart';
import '../../theme/aurora_theme.dart';
import '../../widgets/aurora/aurora.dart';
import '../../utils/currency_utils.dart';
import '../../widgets/common/network_error_screen.dart';
import '../../widgets/customer/banner_carousel.dart';
import 'background_location_disclosure_screen.dart';
import 'driver_orders_screen.dart';
import 'driver_gigs_screen.dart';
import 'driver_earnings_screen.dart';
import 'driver_profile_screen.dart';
import 'driver_restaurant_onboarding_screen.dart';
import 'driver_wallet_screen.dart';

class DriverDashboard extends StatefulWidget {
  const DriverDashboard({super.key});

  @override
  State<DriverDashboard> createState() => _DriverDashboardState();
}

class _DriverDashboardState extends State<DriverDashboard>
    with WidgetsBindingObserver {
  int _currentIndex = 0;
  bool _isOnline = false;
  bool _isWebSocketInitialized = false;
  Timer? _locationTimer;
  Timer? _orderPollingTimer;
  Timer? _onlineDurationTimer;
  bool _isPollingOrders = false;
  final Set<int> _knownAssignedOrderIds = {};
  final LocationService _locationService = LocationService();
  final PartnerApplicationService _applicationService =
      PartnerApplicationService.instance;
  final ApiService _api = ApiService();
  GoogleMapController? _dashboardMapController;
  LatLng? _driverLocation;
  List<dynamic> _dashboardBanners = const [];
  List<Map<String, dynamic>> _deliveryAreas = const [];
  Map<String, dynamic>? _driverZoneArea;
  bool _isLocatingDriver = true;
  bool _isRequestingMapLocationPermission = false;

  Map<String, dynamic> _stats = {};
  String? _loadError;
  Map<String, dynamic>? _activeGig;
  DateTime? _onlineStartedAt;
  bool _isLoading = true;
  int get _safeCurrentIndex => _currentIndex.clamp(0, 3).toInt();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadDriverStatus();
    _loadStats();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadDriverLocation(requestPermission: true);
      _initWebSocket();
    });
  }

  Future<void> _initWebSocket() async {
    if (_isWebSocketInitialized) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    if (authProvider.currentUser == null) {
      await authProvider.loadUser();
    }

    final user = authProvider.currentUser;
    if (user == null) return;

    await _rememberAssignedOrders();
    _startOrderPollingFallback();

    await WebSocketService().initDriver(
      user.id,
      onOrderAssigned: (order) {
        final orderId = _parseId(order['id'] ?? order['order_id']);
        if (orderId != null) _knownAssignedOrderIds.add(orderId);
        _loadStats();
        if (!mounted) return;
        IncomingOrderAlertService.instance.handleIncomingOrderData({
          ...order,
          'role': 'driver',
          'type': 'driver_order_assigned',
        }, source: IncomingOrderSource.websocket);
      },
      onOrderEvent: (_) {
        _loadStats();
      },
    );

    _isWebSocketInitialized = true;
  }

  void _startOrderPollingFallback() {
    _orderPollingTimer?.cancel();
    _orderPollingTimer = Timer.periodic(const Duration(seconds: 12), (_) {
      _pollForAssignedOrders();
    });
  }

  Future<void> _rememberAssignedOrders() async {
    try {
      final response = await _api.get(ApiConstants.driverOrders);
      if (response['success'] == true) {
        for (final order in _extractOrders(response['data'])) {
          if (order is! Map) continue;
          final orderId = _parseId(order['id'] ?? order['order_id']);
          if (orderId != null) _knownAssignedOrderIds.add(orderId);
        }
      }
    } catch (e) {
      debugPrint('Remember driver orders error: $e');
    }
  }

  Future<void> _pollForAssignedOrders() async {
    if (_isPollingOrders || !mounted) return;
    _isPollingOrders = true;

    try {
      final response = await _api.get(ApiConstants.driverOrders);
      if (response['success'] != true || !mounted) return;

      for (final rawOrder in _extractOrders(response['data'])) {
        if (rawOrder is! Map) continue;
        final order = Map<String, dynamic>.from(rawOrder);
        final orderId = _parseId(order['id'] ?? order['order_id']);
        if (orderId == null || _knownAssignedOrderIds.contains(orderId)) {
          continue;
        }

        _knownAssignedOrderIds.add(orderId);
        await _loadStats();
        if (!mounted) return;
        await IncomingOrderAlertService.instance.handleIncomingOrderData({
          ...order,
          'role': 'driver',
          'type': 'driver_order_assigned',
        }, source: IncomingOrderSource.websocket);
        break;
      }
    } catch (e) {
      debugPrint('Driver order polling error: $e');
    } finally {
      _isPollingOrders = false;
    }
  }

  List<dynamic> _extractOrders(dynamic data) {
    if (data is List) return data;
    if (data is Map && data['data'] is List) return data['data'] as List;
    if (data is Map && data['orders'] is List) return data['orders'] as List;
    return const [];
  }

  int? _parseId(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }

  DateTime? _parseOnlineStartedAt(dynamic value) {
    if (value is DateTime) return value.toLocal();
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value)?.toLocal();
    }
    return null;
  }

  String get _greeting {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  }

  String get _onlineDurationText {
    if (!_isOnline || _onlineStartedAt == null) return 'Offline';
    final elapsed = DateTime.now().difference(_onlineStartedAt!);
    final hours = elapsed.inHours;
    final minutes = elapsed.inMinutes.remainder(60);
    if (hours <= 0) return '${minutes}m online';
    return '${hours}h ${minutes.toString().padLeft(2, '0')}m online';
  }

  String get _onlineStartedAtText {
    if (_onlineStartedAt == null) return '';
    return 'Since ${DateFormat('h:mm a').format(_onlineStartedAt!)}';
  }

  bool get _hasVisibleDriverRating {
    final count = int.tryParse('${_stats['total_ratings'] ?? 0}') ?? 0;
    final rating = double.tryParse('${_stats['rating'] ?? ''}');
    return count >= 3 && rating != null && rating > 0;
  }

  Future<void> _loadDriverStatus() async {
    try {
      final response = await _api.get(ApiConstants.driverStatus);
      if (response['success'] == true) {
        setState(() {
          _isOnline = response['data']?['is_online'] ?? false;
          _activeGig = response['data']?['active_gig'];
          _onlineStartedAt = _isOnline
              ? _parseOnlineStartedAt(response['data']?['online_started_at']) ??
                  DateTime.now()
              : null;
        });
        if (_isOnline) {
          if (!await _ensureOnlineLocationPermission()) {
            _stopOnlineDurationTimer();
            return;
          }

          await ForegroundServiceManager.startForegroundService(
            status: 'Online and sharing live location',
            trackLocation: true,
          );
          _startLocationTracking();
          _startOnlineDurationTimer();
        } else {
          _stopOnlineDurationTimer();
        }
      }
    } catch (e) {
      debugPrint('Load driver status error: $e');
    }
  }

  Future<void> _loadStats() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    try {
      final response = await _api.get('/driver/stats');
      if (mounted && response['success'] == true) {
        setState(() {
          _stats = response['data'] ?? {};
          _activeGig = _stats['active_gig'];
          _loadError = null;
        });
      }
      await _loadDashboardBanners();
    } catch (e) {
      debugPrint('Load stats error: $e');
      if (mounted && _stats.isEmpty) {
        setState(() => _loadError = _cleanApiError(e));
      }
    }
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _loadDashboardBanners() async {
    try {
      final response = await _api.get('${ApiConstants.bannersByType}/driver');
      if (!mounted) return;
      setState(() {
        _dashboardBanners =
            response['success'] == true && response['data'] is List
                ? List<dynamic>.from(response['data'])
                : const [];
      });
    } catch (e) {
      debugPrint('Driver banner load error: $e');
      if (mounted) setState(() => _dashboardBanners = const []);
    }
  }

  Future<void> _toggleOnlineStatus() async {
    if (!_isOnline && _activeGig == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Book an active gig before going online.'),
          action: SnackBarAction(
            label: 'Gigs',
            textColor: Colors.white,
            onPressed: _openGigsSheet,
          ),
          backgroundColor: Colors.red,
        ),
      );
      _openGigsSheet();
      return;
    }

    if (!_isOnline && !await _ensureBatteryOptimizationDisabled()) {
      return;
    }

    if (!_isOnline && !await _ensureOnlineLocationPermission()) {
      return;
    }

    try {
      final response = await _api.post(
        ApiConstants.driverToggleStatus,
        data: {'is_online': !_isOnline},
      );
      if (response['success'] == true) {
        final isOnline = response['data']?['is_online'] ?? !_isOnline;
        setState(() {
          _isOnline = isOnline;
          _activeGig = response['data']?['active_gig'] ?? _activeGig;
          _onlineStartedAt = isOnline
              ? _parseOnlineStartedAt(response['data']?['online_started_at']) ??
                  DateTime.now()
              : null;
        });

        if (_isOnline) {
          await ForegroundServiceManager.startForegroundService(
            status: 'Online and sharing live location',
            trackLocation: true,
          );
          _startLocationTracking();
          _startOnlineDurationTimer();
          HapticFeedback.lightImpact();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                children: const [
                  Icon(Icons.circle, color: Colors.green, size: 12),
                  SizedBox(width: 8),
                  Text('You are now online and ready to accept deliveries'),
                ],
              ),
              backgroundColor: Colors.green,
              duration: const Duration(seconds: 2),
            ),
          );
        } else {
          _stopLocationTracking();
          _stopOnlineDurationTimer();
          await ForegroundServiceManager.stopForegroundService();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                children: const [
                  Icon(Icons.circle, color: Colors.red, size: 12),
                  SizedBox(width: 8),
                  Text('You are now offline'),
                ],
              ),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      debugPrint('Toggle status error: $e');
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to toggle status: $e')));
    }
  }

  String _cleanApiError(Object error) {
    final message = error.toString();
    if (message.startsWith('Exception: ')) {
      return message.substring('Exception: '.length);
    }
    return message;
  }

  Future<bool> _ensureBatteryOptimizationDisabled() async {
    final disabled =
        await OrderAlertPermissionManager.isBatteryOptimizationDisabled();
    if (disabled) return true;

    if (!mounted) return false;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Allow unrestricted battery usage before going online.',
        ),
      ),
    );
    await OrderAlertPermissionManager.requestBatteryOptimizationExemption();
    return false;
  }

  Future<bool> _ensureOnlineLocationPermission() async {
    if (!await _locationService.isLocationServiceEnabled()) {
      _showLocationPermissionMessage(
        'Turn on location services before going online.',
      );
      await _locationService.openLocationSettings();
      return false;
    }

    var permission = await _locationService.checkLocationPermission();
    if (permission == LocationPermission.denied) {
      if (!await _ensureBackgroundLocationDisclosureAccepted(
        forceDisclosure: true,
      )) {
        _showLocationPermissionMessage(
          'Background location consent is required before going online.',
        );
        return false;
      }
      await _locationService.requestLocationPermission();
      permission = await _locationService.checkLocationPermission();
    }

    if (!await _ensureBackgroundLocationDisclosureAccepted()) {
      _showLocationPermissionMessage(
        'Background location consent is required before going online.',
      );
      return false;
    }

    if (permission == LocationPermission.always) {
      return true;
    }

    if (permission == LocationPermission.whileInUse) {
      if (!await _ensureBackgroundLocationDisclosureAccepted(
        forceDisclosure: true,
      )) {
        _showLocationPermissionMessage(
          'Background location consent is required before going online.',
        );
        return false;
      }

      _showLocationPermissionMessage(
        'Choose "Allow all the time" in location permission settings.',
      );
      await _locationService.openAppLocationSettings();
      return false;
    }

    _showLocationPermissionSnackBar(
      'Allow location permission before going online.',
      _locationService.openAppLocationSettings,
    );
    return false;
  }

  Future<bool> _ensureBackgroundLocationDisclosureAccepted({
    bool forceDisclosure = false,
  }) async {
    if (!mounted) return false;
    return BackgroundLocationDisclosureScreen.ensureAccepted(
      context,
      forceDisclosure: forceDisclosure,
    );
  }

  void _showLocationPermissionSnackBar(
    String message,
    Future<bool> Function() action, {
    String actionLabel = 'Settings',
  }) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        action: SnackBarAction(
          label: actionLabel,
          textColor: Colors.white,
          onPressed: () => action(),
        ),
        backgroundColor: foodflow.orange,
      ),
    );
  }

  void _showLocationPermissionMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: foodflow.orange,
      ),
    );
  }

  void _startLocationTracking() {
    _locationTimer?.cancel();
    _updateLocation(); // Immediate update
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (timer) {
      if (_isOnline && mounted) {
        _updateLocation();
      } else if (!_isOnline) {
        _locationTimer?.cancel();
      }
    });
  }

  void _stopLocationTracking() {
    _locationTimer?.cancel();
    _locationTimer = null;
  }

  void _startOnlineDurationTimer() {
    _onlineDurationTimer?.cancel();
    _onlineDurationTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      if (mounted && _isOnline) setState(() {});
    });
  }

  void _stopOnlineDurationTimer() {
    _onlineDurationTimer?.cancel();
    _onlineDurationTimer = null;
  }

  Future<void> _updateLocation() async {
    try {
      final token = await _api.getToken();
      if (token == null) {
        _stopLocationTracking();
        return;
      }

      final position = await _locationService.getCurrentLocation(
        requestPermission: false,
      );
      if (position != null && mounted) {
        final location = LatLng(position.latitude, position.longitude);
        await _ensureDeliveryAreasLoaded();
        if (!mounted) return;
        final zone = _resolveAreaFromCoordinates(
          position.latitude,
          position.longitude,
        );
        setState(() {
          _driverLocation = location;
          _driverZoneArea = zone;
          _isLocatingDriver = false;
        });
        _moveDashboardMap(location);
        await _api.post(
          ApiConstants.driverLocation,
          data: {'lat': position.latitude, 'lng': position.longitude},
        );
      }
    } catch (e) {
      debugPrint('Update location error: $e');
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _stopLocationTracking();
    _stopOnlineDurationTimer();
    _orderPollingTimer?.cancel();
    WebSocketService().dispose();
    SoundService.stopIncomingOrderAlarm();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final topPadding = MediaQuery.of(context).padding.top;

    return Scaffold(
      backgroundColor: foodflow.canvas,
      appBar: PreferredSize(
        preferredSize: Size.fromHeight(topPadding + 60),
        child: _buildAppBar(),
      ),
      body: Stack(
        children: [
          Positioned.fill(child: Stack(children: AuroraTheme.auroraBlobs())),
          Positioned.fill(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _loadError != null && _stats.isEmpty
                    ? NetworkErrorView(message: _loadError, onRetry: _loadStats)
                    : _buildCurrentBody(),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(top: false, child: _buildBottomNavBar()),
    );
  }

  Widget _buildCurrentBody() {
    return IndexedStack(
      index: _safeCurrentIndex,
      children: [
        _buildDashboard(),
        const DriverOrdersScreen(),
        const DriverEarningsScreen(),
        const DriverWalletScreen(),
      ],
    );
  }

  Future<bool> _ensureMapLocationPermission() async {
    if (!await _locationService.isLocationServiceEnabled()) {
      _showLocationPermissionMessage(
        'Turn on location services to show your live map.',
      );
      await _locationService.openLocationSettings();
      return false;
    }

    var permission = await _locationService.checkLocationPermission();
    if (permission == LocationPermission.denied) {
      if (!await _ensureBackgroundLocationDisclosureAccepted(
        forceDisclosure: true,
      )) {
        _showLocationPermissionMessage(
          'Location consent is required to show your live map.',
        );
        return false;
      }

      await _locationService.requestLocationPermission();
      permission = await _locationService.checkLocationPermission();
    }

    if (permission == LocationPermission.always ||
        permission == LocationPermission.whileInUse) {
      return true;
    }

    _showLocationPermissionSnackBar(
      'Allow location permission from app settings to show your live map.',
      _locationService.openAppLocationSettings,
    );
    return false;
  }

  Future<void> _requestMapLocationPermission() async {
    if (_isRequestingMapLocationPermission) return;

    setState(() {
      _isRequestingMapLocationPermission = true;
      _isLocatingDriver = true;
    });

    try {
      await _loadDriverLocation(requestPermission: true);
    } finally {
      if (mounted) {
        setState(() => _isRequestingMapLocationPermission = false);
      }
    }
  }

  Future<void> _loadDriverLocation({bool requestPermission = false}) async {
    try {
      if (requestPermission && !await _ensureMapLocationPermission()) {
        if (mounted) setState(() => _isLocatingDriver = false);
        return;
      }

      final position = await _locationService.getCurrentLocation(
        requestPermission: false,
      );
      if (!mounted) return;
      if (position == null) {
        setState(() {
          _driverZoneArea = null;
          _isLocatingDriver = false;
        });
        return;
      }

      final location = LatLng(position.latitude, position.longitude);
      await _ensureDeliveryAreasLoaded();
      if (!mounted) return;
      final zone = _resolveAreaFromCoordinates(
        position.latitude,
        position.longitude,
      );
      setState(() {
        _driverLocation = location;
        _driverZoneArea = zone;
        _isLocatingDriver = false;
      });
      _moveDashboardMap(location);
    } catch (e) {
      debugPrint('Load driver location error: $e');
      if (mounted) setState(() => _isLocatingDriver = false);
    }
  }

  Future<void> _ensureDeliveryAreasLoaded() async {
    if (_deliveryAreas.isNotEmpty) return;

    try {
      final areas = await _applicationService.fetchDeliveryAreas();
      if (!mounted) return;
      setState(() => _deliveryAreas = areas);
    } catch (e) {
      debugPrint('Load driver delivery zones error: $e');
    }
  }

  Map<String, dynamic>? _resolveAreaFromCoordinates(
    double latitude,
    double longitude,
  ) {
    final containing = _deliveryAreas
        .where((area) => _areaContainsPoint(area, latitude, longitude))
        .toList()
      ..sort(
        (left, right) => _areaFootprint(left).compareTo(_areaFootprint(right)),
      );

    return containing.isEmpty ? null : containing.first;
  }

  bool _areaContainsPoint(
    Map<String, dynamic> area,
    double latitude,
    double longitude,
  ) {
    if ((area['area_type']?.toString() ?? 'circle') == 'polygon') {
      final polygon = _polygonPointsFromArea(area);
      if (polygon.length < 3) return false;
      return _pointInPolygon(polygon, latitude, longitude);
    }

    final areaLatitude = _doubleValue(area['latitude']);
    final areaLongitude = _doubleValue(area['longitude']);
    final radius = _doubleValue(area['radius_km']);
    if (areaLatitude == null || areaLongitude == null || radius == null) {
      return false;
    }

    return _distanceKm(latitude, longitude, areaLatitude, areaLongitude) <=
        radius;
  }

  bool _pointInPolygon(
    List<LatLng> polygon,
    double latitude,
    double longitude,
  ) {
    var intersections = 0;
    for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
      final current = polygon[i];
      final previous = polygon[j];
      final intersects =
          (current.latitude > latitude) != (previous.latitude > latitude) &&
              longitude <
                  (previous.longitude - current.longitude) *
                          (latitude - current.latitude) /
                          (previous.latitude - current.latitude) +
                      current.longitude;
      if (intersects) intersections++;
    }
    return intersections.isOdd;
  }

  double _areaFootprint(Map<String, dynamic> area) {
    if ((area['area_type']?.toString() ?? 'circle') == 'polygon') {
      final polygon = _polygonPointsFromArea(area);
      if (polygon.length < 3) return double.infinity;

      var total = 0.0;
      for (var i = 0; i < polygon.length; i++) {
        final current = polygon[i];
        final next = polygon[(i + 1) % polygon.length];
        total += current.latitude * next.longitude;
        total -= next.latitude * current.longitude;
      }
      final polygonArea = total.abs() / 2;
      return polygonArea > 0 ? polygonArea : double.infinity;
    }

    final radius = _doubleValue(area['radius_km']);
    return radius == null || radius <= 0 ? double.infinity : radius;
  }

  List<LatLng> _polygonPointsFromArea(Map<String, dynamic> area) {
    final polygon = area['polygon_coordinates'];
    if (polygon is! List) return const [];

    return polygon
        .map(_latLngFromMap)
        .whereType<LatLng>()
        .toList(growable: false);
  }

  LatLng? _latLngFromMap(dynamic value) {
    if (value is! Map) return null;
    final latitude = _doubleValue(value['lat'] ?? value['latitude']);
    final longitude = _doubleValue(
      value['lng'] ?? value['lon'] ?? value['longitude'],
    );
    if (latitude == null || longitude == null) return null;
    if (latitude < -90 ||
        latitude > 90 ||
        longitude < -180 ||
        longitude > 180) {
      return null;
    }
    return LatLng(latitude, longitude);
  }

  double? _doubleValue(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString() ?? '');
  }

  double _distanceKm(double lat1, double lon1, double lat2, double lon2) {
    const earthRadius = 6371.0;
    final dLat = _degreesToRadians(lat2 - lat1);
    final dLon = _degreesToRadians(lon2 - lon1);
    final a = (sin(dLat / 2) * sin(dLat / 2)) +
        cos(_degreesToRadians(lat1)) *
            cos(_degreesToRadians(lat2)) *
            (sin(dLon / 2) * sin(dLon / 2));
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return earthRadius * c;
  }

  double _degreesToRadians(double degrees) => degrees * pi / 180;

  Set<Polygon> _buildZonePolygons() {
    final area = _driverZoneArea;
    if (area == null ||
        (area['area_type']?.toString() ?? 'circle') != 'polygon') {
      return const {};
    }

    final points = _polygonPointsFromArea(area);
    if (points.length < 3) return const {};

    return {
      Polygon(
        polygonId: const PolygonId('driver_zone_mask'),
        points: points,
        fillColor: foodflow.orange.withOpacity(0.16),
        strokeColor: foodflow.orange.withOpacity(0.72),
        strokeWidth: 3,
      ),
    };
  }

  Set<Circle> _buildZoneCircles() {
    final area = _driverZoneArea;
    if (area == null ||
        (area['area_type']?.toString() ?? 'circle') == 'polygon') {
      return const {};
    }

    final latitude = _doubleValue(area['latitude']);
    final longitude = _doubleValue(area['longitude']);
    final radiusKm = _doubleValue(area['radius_km']);
    if (latitude == null ||
        longitude == null ||
        radiusKm == null ||
        radiusKm <= 0) {
      return const {};
    }

    return {
      Circle(
        circleId: const CircleId('driver_zone_mask'),
        center: LatLng(latitude, longitude),
        radius: radiusKm * 1000,
        fillColor: foodflow.orange.withOpacity(0.16),
        strokeColor: foodflow.orange.withOpacity(0.72),
        strokeWidth: 3,
      ),
    };
  }

  String? get _driverZoneName {
    final name = _driverZoneArea?['name']?.toString().trim();
    return name == null || name.isEmpty ? null : name;
  }

  Future<void> _openGigsSheet() async {
    final booked = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: foodflow.surfaceColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
      ),
      builder: (context) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.88,
        minChildSize: 0.55,
        maxChildSize: 0.95,
        builder: (context, scrollController) {
          return const DriverGigsScreen();
        },
      ),
    );

    if (booked == true && mounted) {
      await _loadDriverStatus();
      await _loadStats();
    }
  }

  Future<void> _showProfileMenu() async {
    final user = Provider.of<AuthProvider>(context, listen: false).currentUser;
    final driverName = user != null && user.name.trim().isNotEmpty
        ? user.name.trim()
        : 'Driver';
    final contact = (user?.phone ?? user?.email ?? '').trim();

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: foodflow.surfaceColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (sheetContext) {
        return SafeArea(
          top: false,
          child: Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              12,
              20,
              MediaQuery.of(sheetContext).viewInsets.bottom + 20,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE5EAF1),
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    CircleAvatar(
                      radius: 28,
                      backgroundColor: foodflow.orange,
                      child: Text(
                        driverName.characters.first.toUpperCase(),
                        style: GoogleFonts.plusJakartaSans(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            driverName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(sheetContext).textTheme.titleLarge,
                          ),
                          if (contact.isNotEmpty)
                            Text(
                              contact,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: GoogleFonts.plusJakartaSans(
                                color: foodflow.muted,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.person_outline,
                  label: 'Profile',
                  onTap: () => _openAfterProfileSheet(
                    sheetContext,
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const DriverProfileScreen(
                          showAppBar: true,
                        ),
                      ),
                    ),
                  ),
                ),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.account_balance_wallet_outlined,
                  label: 'Wallet',
                  onTap: () => _openAfterProfileSheet(sheetContext, () {
                    setState(() => _currentIndex = 3);
                  }),
                ),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.add_business_outlined,
                  label: 'Onboard Restaurant',
                  onTap: () => _openAfterProfileSheet(
                    sheetContext,
                    () => Navigator.pushNamed(
                      context,
                      '/driver/restaurant-onboardings',
                    ),
                  ),
                ),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.notifications_none_outlined,
                  label: 'Notifications',
                  onTap: () => _openAfterProfileSheet(
                    sheetContext,
                    () => Navigator.pushNamed(context, '/driver/notifications'),
                  ),
                ),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.support_agent_outlined,
                  label: 'Support',
                  onTap: () => _openAfterProfileSheet(
                    sheetContext,
                    () => Navigator.pushNamed(context, '/driver/support'),
                  ),
                ),
                _profileMenuTile(
                  sheetContext,
                  icon: Icons.privacy_tip_outlined,
                  label: 'Privacy & legal',
                  onTap: () => _openAfterProfileSheet(
                    sheetContext,
                    () => Navigator.pushNamed(context, '/privacy-legal'),
                  ),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => _openAfterProfileSheet(
                      sheetContext,
                      () async {
                        await context.read<AuthProvider>().logout();
                        if (!mounted) return;
                        Navigator.of(context, rootNavigator: true)
                            .pushNamedAndRemoveUntil(
                                '/login', (route) => false);
                      },
                    ),
                    icon:  Icon(Icons.logout, color: foodflow.danger),
                    label: const Text('Logout'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: foodflow.danger,
                      side:
                          BorderSide(color: foodflow.danger.withOpacity(0.24)),
                      minimumSize: const Size.fromHeight(52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _openAfterProfileSheet(
      BuildContext sheetContext, FutureOr<void> Function() action) {
    Navigator.pop(sheetContext);
    Future<void>.microtask(() async {
      if (!mounted) return;
      await action();
    });
  }

  Widget _profileMenuTile(
    BuildContext sheetContext, {
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: foodflow.orange),
      title: Text(
        label,
        style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800),
      ),
      trailing:  Icon(Icons.chevron_right, color: foodflow.muted),
      onTap: onTap,
    );
  }

  Widget _buildAppBar() {
    final user = Provider.of<AuthProvider>(context).currentUser;
    final driverName = user != null && user.name.trim().isNotEmpty
        ? user.name.trim()
        : 'Driver';
    final branchLabel = user?.hasBranch == true ? user!.branchLabel : null;

    return Container(
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top,
        left: 16,
        right: 16,
      ),
      decoration: BoxDecoration(
        color: foodflow.canvas.withOpacity(0.92),
        border: Border(bottom: BorderSide(color: foodflow.glassBorder)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 7),
          Row(
            children: [
              InkWell(
                onTap: _showProfileMenu,
                borderRadius: BorderRadius.circular(14),
                child: Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: foodflow.orange,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    user != null && user.name.isNotEmpty
                        ? user.name[0].toUpperCase()
                        : 'D',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$_greeting, $driverName',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: foodflow.ink,
                      ),
                    ),
                    Text(
                      _activeGig == null
                          ? 'Book a gig before accepting orders'
                          : _isOnline
                              ? 'You are receiving orders'
                              : 'You are offline',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 12,
                        height: 1.1,
                        color: foodflow.muted,
                      ),
                    ),
                    if (branchLabel != null)
                      Text(
                        branchLabel,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: foodflow.orange,
                        ),
                      ),
                  ],
                ),
              ),
              if (_isOnline) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 9,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: foodflow.success.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: foodflow.success.withOpacity(0.24),
                    ),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        _onlineDurationText,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          height: 1.0,
                          fontWeight: FontWeight.w800,
                          color: foodflow.success,
                        ),
                      ),
                      if (_onlineStartedAtText.isNotEmpty)
                        Text(
                          _onlineStartedAtText,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 9,
                            height: 1.0,
                            fontWeight: FontWeight.w800,
                            color: foodflow.muted,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
              const SizedBox(width: 4),
              IconButton(
                onPressed: () =>
                    Navigator.pushNamed(context, '/driver/notifications'),
                icon:
                    Icon(Icons.notifications_none_rounded, color: foodflow.ink),
                tooltip: 'Notifications',
                visualDensity: VisualDensity.compact,
              ),
              const SizedBox(width: 4),
              GestureDetector(
                onTap: _toggleOnlineStatus,
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  width: 56,
                  height: 32,
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: _isOnline
                        ? foodflow.success
                        : foodflow.line,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  alignment:
                      _isOnline ? Alignment.centerRight : Alignment.centerLeft,
                  child: Container(
                    width: 24,
                    height: 24,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
        ],
      ),
    );
  }

  Widget _buildQuickStat(
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Expanded(
      child: SizedBox(
        height: 76,
        child: GlassCard(
          solid: true,
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
          radius: 16,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 30,
                height: 30,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.14),
                  borderRadius: BorderRadius.circular(9),
                ),
                child: Icon(icon, color: color, size: 16),
              ),
              const SizedBox(height: 6),
              Text(
                value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                  height: 1.0,
                  color: foodflow.ink,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 9.5,
                  height: 1.0,
                  color: foodflow.muted,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _sectionHeader(String title, {VoidCallback? onViewAll}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: foodflow.ink,
            ),
          ),
          if (onViewAll != null)
            TextButton(
              onPressed: onViewAll,
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                minimumSize: const Size(0, 0),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: const Text('View all'),
            ),
        ],
      ),
    );
  }

  Widget _buildDashboard() {
    var step = 0;
    Widget stagger(Widget child) => AuroraEntrance(
          delay: Duration(milliseconds: 40 + (step++) * 55),
          child: child,
        );

    return RefreshIndicator(
      onRefresh: _loadStats,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Live map + presence control
            stagger(
              GlassCard(
                solid: true,
                padding: EdgeInsets.zero,
                radius: 22,
                child: SizedBox(
                  height: 420,
                  child: Stack(
                    children: [
                      Positioned.fill(child: _buildDriverMap()),
                      Positioned(
                        top: 16,
                        left: 16,
                        child: Material(
                          color: foodflow.surfaceColor,
                          shape: const CircleBorder(),
                          elevation: 3,
                          shadowColor: Colors.black26,
                          child: InkWell(
                            onTap: _loadDriverLocation,
                            customBorder: const CircleBorder(),
                            child: SizedBox(
                              width: 42,
                              height: 42,
                              child: Icon(Icons.my_location,
                                  color: foodflow.orange, size: 20),
                            ),
                          ),
                        ),
                      ),
                      Positioned(
                        left: 14,
                        right: 14,
                        bottom: 14,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(16),
                          child: BackdropFilter(
                            filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: foodflow.surfaceColor.withOpacity(0.86),
                                borderRadius: BorderRadius.circular(16),
                                border:
                                    Border.all(color: foodflow.glassBorder),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 10,
                                    height: 10,
                                    margin: const EdgeInsets.only(right: 10),
                                    decoration: BoxDecoration(
                                      shape: BoxShape.circle,
                                      color: _isOnline
                                          ? foodflow.success
                                          : foodflow.faint,
                                    ),
                                  ),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(
                                          _isOnline
                                              ? 'You are online'
                                              : 'You are offline',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 15,
                                            fontWeight: FontWeight.w800,
                                            color: foodflow.ink,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          _isOnline
                                              ? 'Stay ready for incoming orders'
                                              : _activeGig == null
                                                  ? 'Book a gig to start'
                                                  : 'Go online to start',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 11.5,
                                            color: foodflow.muted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  GlassButton(
                                    expand: false,
                                    compact: true,
                                    label: _activeGig == null
                                        ? 'Book Gig'
                                        : _isOnline
                                            ? 'Online'
                                            : 'Go Online',
                                    onPressed: _activeGig == null
                                        ? _openGigsSheet
                                        : _toggleOnlineStatus,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            if (_dashboardBanners.isNotEmpty) ...[
              const SizedBox(height: 14),
              stagger(BannerCarousel(banners: _dashboardBanners)),
            ],
            const SizedBox(height: 16),
            stagger(
              Row(
                children: [
                  _buildQuickStat(
                    'Today',
                    formatCurrencyValue(context, _stats['today_earnings']),
                    Icons.payments_outlined,
                    foodflow.success,
                  ),
                  const SizedBox(width: 10),
                  _buildQuickStat(
                    'Orders',
                    '${_stats['today_deliveries'] ?? 0}',
                    Icons.shopping_bag_outlined,
                    foodflow.orange,
                  ),
                  const SizedBox(width: 10),
                  _buildQuickStat(
                    'Rating',
                    _hasVisibleDriverRating ? '${_stats['rating']}' : 'New',
                    Icons.star_rounded,
                    const Color(0xFFF59E0B),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),
            stagger(_sectionHeader('Running Orders',
                onViewAll: () => setState(() => _currentIndex = 1))),
            stagger(_buildRunningOrders()),
            const SizedBox(height: 12),
            stagger(_buildRestaurantOnboardingCard()),
            const SizedBox(height: 24),

            stagger(_sectionHeader('Earnings Summary',
                onViewAll: () => setState(() => _currentIndex = 2))),
            stagger(
              GlassCard(
                child: Row(
                  children: [
                    Expanded(
                      child: _miniEarning('This Week',
                          formatCurrencyValue(context, _stats['week_earnings'])),
                    ),
                    Container(width: 1, height: 40, color: foodflow.line),
                    Expanded(
                      child: _miniEarning(
                          'This Month',
                          formatCurrencyValue(
                              context, _stats['month_earnings'])),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            stagger(_sectionHeader('Recent Deliveries',
                onViewAll: () => setState(() => _currentIndex = 1))),
            stagger(_buildRecentDeliveries()),
          ],
        ),
      ),
    );
  }

  Widget _miniEarning(String label, String value) {
    return Column(
      children: [
        Text(label, style: TextStyle(color: foodflow.muted, fontSize: 12)),
        const SizedBox(height: 4),
        Text(
          value,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: foodflow.success,
          ),
        ),
      ],
    );
  }

  Widget _buildRestaurantOnboardingCard() {
    return GlassCard(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => const DriverRestaurantOnboardingScreen(),
          ),
        );
      },
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: foodflow.orange.withOpacity(0.14),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.add_business_outlined, color: foodflow.orange),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Onboard Restaurant',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: foodflow.ink,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  'Register restaurants and track incentive status.',
                  style: TextStyle(color: foodflow.muted, fontSize: 12),
                ),
              ],
            ),
          ),
          Icon(Icons.arrow_forward_rounded, color: foodflow.faint, size: 20),
        ],
      ),
    );
  }

  Widget _buildRecentDeliveries() {
    final recentDeliveries = _stats['recent_deliveries'] as List? ?? [];

    if (recentDeliveries.isEmpty) {
      return GlassCard(
        padding: const EdgeInsets.all(28),
        child: Column(
          children: [
            Icon(Icons.delivery_dining_outlined,
                size: 56, color: foodflow.faint),
            const SizedBox(height: 14),
            Text(
              'No deliveries yet',
              style: GoogleFonts.plusJakartaSans(
                  color: foodflow.muted, fontWeight: FontWeight.w700),
            ),
            if (!_isOnline) ...[
              const SizedBox(height: 6),
              Text(
                'Go online to start receiving orders',
                style: TextStyle(fontSize: 12, color: foodflow.faint),
              ),
            ],
          ],
        ),
      );
    }

    final count = recentDeliveries.length > 3 ? 3 : recentDeliveries.length;
    return Column(
      children: [
        for (var index = 0; index < count; index++)
          Builder(builder: (context) {
            final delivery = recentDeliveries[index];
            return AuroraEntrance(
              delay: Duration(milliseconds: index * 55),
              child: GlassCard(
                margin: const EdgeInsets.only(bottom: 12),
                child: Row(
                  children: [
                    Container(
                      width: 46,
                      height: 46,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: foodflow.success.withOpacity(0.14),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.check_rounded,
                          color: foodflow.success),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Order #${delivery['order_number']}',
                            style: GoogleFonts.plusJakartaSans(
                              fontWeight: FontWeight.w800,
                              color: foodflow.ink,
                            ),
                          ),
                          Text(
                            '${delivery['customer_name']} • ${delivery['delivery_address']?.toString().split(',').first ?? ''}',
                            style:
                                TextStyle(fontSize: 12, color: foodflow.muted),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          formatCurrencyValue(
                              context, delivery['delivery_fee'] ?? 50),
                          style: GoogleFonts.plusJakartaSans(
                            fontWeight: FontWeight.w800,
                            color: foodflow.success,
                          ),
                        ),
                        Text(
                          _safeDate(delivery['delivered_at'] ??
                              delivery['created_at']),
                          style:
                              TextStyle(fontSize: 10, color: foodflow.faint),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          }),
      ],
    );
  }

  String _safeDate(dynamic value) {
    final parsed = DateTime.tryParse(value?.toString() ?? '');
    return parsed == null ? '' : DateFormat('dd MMM').format(parsed);
  }

  Widget _buildDriverMap() {
    final location = _driverLocation ?? const LatLng(28.6139, 77.2090);

    return Stack(
      children: [
        GoogleMap(
          onMapCreated: (controller) {
            _dashboardMapController = controller;
            if (_driverLocation != null) {
              controller.moveCamera(
                CameraUpdate.newCameraPosition(
                  CameraPosition(target: _driverLocation!, zoom: 16),
                ),
              );
            }
          },
          initialCameraPosition: CameraPosition(target: location, zoom: 15),
          myLocationEnabled: _driverLocation != null,
          myLocationButtonEnabled: false,
          zoomControlsEnabled: false,
          compassEnabled: false,
          polygons: _buildZonePolygons(),
          circles: _buildZoneCircles(),
          markers: {
            if (_driverLocation != null)
              Marker(
                markerId: const MarkerId('driver_current_location'),
                position: _driverLocation!,
                infoWindow: const InfoWindow(title: 'Your location'),
                icon: BitmapDescriptor.defaultMarkerWithHue(
                  BitmapDescriptor.hueRed,
                ),
              ),
          },
        ),
        if (_driverZoneName != null)
          Positioned(
            top: 12,
            left: 12,
            right: 12,
            child: Align(
              alignment: Alignment.centerLeft,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: foodflow.surfaceColor.withOpacity(0.96),
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: const [
                    BoxShadow(
                      color: Colors.black12,
                      blurRadius: 10,
                      offset: Offset(0, 3),
                    ),
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.map_outlined,
                      color: foodflow.orange,
                      size: 17,
                    ),
                    const SizedBox(width: 7),
                    Text(
                      _driverZoneName!,
                      style: GoogleFonts.plusJakartaSans(
                        color: foodflow.ink,
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        if (_isLocatingDriver)
          Container(
            color: foodflow.canvas.withOpacity(0.72),
            alignment: Alignment.center,
            child: CircularProgressIndicator(
              color: foodflow.orange,
            ),
          ),
        if (!_isLocatingDriver && _driverLocation == null)
          Container(
            color: foodflow.canvas.withOpacity(0.9),
            alignment: Alignment.center,
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.location_off_outlined,
                  color: foodflow.orange,
                  size: 34,
                ),
                const SizedBox(height: 10),
                 Text(
                  'Enable location to show your live map',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: foodflow.ink,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 10),
                ElevatedButton.icon(
                  onPressed: _isRequestingMapLocationPermission
                      ? null
                      : _requestMapLocationPermission,
                  icon: _isRequestingMapLocationPermission
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Icon(Icons.my_location_outlined, size: 18),
                  label: Text(
                    _isRequestingMapLocationPermission
                        ? 'Requesting...'
                        : 'Allow location',
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _currentIndex == 0) {
      _loadDriverLocation();
      _loadStats();
    }
  }

  @override
  void didUpdateWidget(covariant DriverDashboard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_currentIndex == 0 && _driverLocation == null && !_isLocatingDriver) {
      _loadDriverLocation();
    }
  }

  @override
  void deactivate() {
    _dashboardMapController = null;
    super.deactivate();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_currentIndex == 0 && _driverLocation == null && !_isLocatingDriver) {
      _loadDriverLocation();
    }
  }

  @override
  void reassemble() {
    super.reassemble();
    if (_currentIndex == 0) {
      _loadDriverLocation();
    }
  }

  @override
  void activate() {
    super.activate();
    if (_currentIndex == 0 && _driverLocation == null) {
      _loadDriverLocation();
    }
  }

  @override
  void didChangeMetrics() {
    if (_driverLocation != null) {
      _moveDashboardMap(_driverLocation!, animate: false);
    }
  }

  Future<void> _moveDashboardMap(
    LatLng location, {
    bool animate = true,
  }) async {
    final controller = _dashboardMapController;
    if (!mounted || _currentIndex != 0 || controller == null) return;

    try {
      final update = CameraUpdate.newCameraPosition(
        CameraPosition(target: location, zoom: 16),
      );
      if (animate) {
        await controller.animateCamera(update);
      } else {
        await controller.moveCamera(update);
      }
    } catch (_) {
      _dashboardMapController = null;
    }
  }

  Widget _buildRunningOrders() {
    final runningOrders = _stats['running_orders'] as List? ?? [];

    if (runningOrders.isEmpty) {
      return GlassCard(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            Icon(Icons.delivery_dining_outlined,
                color: foodflow.faint, size: 30),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'No running orders right now',
                style: GoogleFonts.plusJakartaSans(
                  color: foodflow.muted,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      );
    }

    final count = runningOrders.length > 3 ? 3 : runningOrders.length;
    return Column(
      children: [
        for (var index = 0; index < count; index++)
          Builder(builder: (context) {
            final order = runningOrders[index] as Map;
            final accepted = order['driver_accepted_at'] != null;
            final tint = accepted ? foodflow.success : foodflow.orange;
            return AuroraEntrance(
              delay: Duration(milliseconds: index * 55),
              child: GlassCard(
                margin: const EdgeInsets.only(bottom: 12),
                onTap: () => Navigator.pushNamed(context, '/driver/order',
                    arguments: order['id']),
                child: Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: tint.withOpacity(0.14),
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Icon(
                        accepted
                            ? Icons.route_rounded
                            : Icons.notifications_active_rounded,
                        color: tint,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Order #${order['order_number'] ?? ''}',
                            style: GoogleFonts.plusJakartaSans(
                              fontWeight: FontWeight.w800,
                              color: foodflow.ink,
                            ),
                          ),
                          Text(
                            accepted
                                ? 'Running delivery'
                                : 'Waiting for your response',
                            style: TextStyle(
                                color: foodflow.muted, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                    Icon(Icons.chevron_right_rounded, color: foodflow.faint),
                  ],
                ),
              ),
            );
          }),
      ],
    );
  }

  Widget _buildBottomNavBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 10),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: foodflow.glassSurface,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: foodflow.glassBorder),
              boxShadow: [
                BoxShadow(
                  color: foodflow.isDark
                      ? Colors.black.withOpacity(0.4)
                      : Colors.black.withOpacity(0.08),
                  blurRadius: 20,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: BottomNavigationBar(
              currentIndex: _safeCurrentIndex,
              onTap: (index) {
                if (index != 0) {
                  _dashboardMapController = null;
                }
                setState(() => _currentIndex = index);
              },
              backgroundColor: Colors.transparent,
              elevation: 0,
            type: BottomNavigationBarType.fixed,
            selectedItemColor: foodflow.orange,
            unselectedItemColor: foodflow.muted,
            selectedLabelStyle: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w800,
            ),
            unselectedLabelStyle: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
            items: const [
              BottomNavigationBarItem(
                icon: Icon(Icons.shopping_bag_outlined),
                activeIcon: Icon(Icons.shopping_bag),
                label: 'New Order',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.receipt_long_outlined),
                activeIcon: Icon(Icons.receipt_long),
                label: 'Orders',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.account_balance_wallet_outlined),
                activeIcon: Icon(Icons.account_balance_wallet),
                label: 'Earnings',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.wallet_outlined),
                activeIcon: Icon(Icons.wallet),
                label: 'Wallet',
              ),
            ],
          ),
          ),
        ),
      ),
    );
  }
}
