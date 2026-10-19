import 'package:flutter/foundation.dart';

import '../config/api_constants.dart';
import '../models/session.dart';
import '../services/api_client.dart';
import '../services/form_helpers.dart';

class OnboardingBloc extends ChangeNotifier {
  OnboardingBloc(Session session) : api = ApiClient(session);

  final ApiClient api;
  bool loading = true;
  String? error;
  Map<String, dynamic> dashboard = {};
  Map<String, dynamic> meta = {};
  List<dynamic> pipeline = [];
  bool metadataRefreshing = false;

  bool get canRestaurants => meta['capabilities']?['restaurants'] == true;
  bool get canDrivers => meta['capabilities']?['drivers'] == true;

  Future<void> refreshMetadata() async {
    metadataRefreshing = true;
    notifyListeners();

    try {
      final result = await api.get(ApiConstants.onboardingMeta);
      meta = (result['data'] as Map).cast<String, dynamic>();
    } catch (e) {
      error = e.toString();
    } finally {
      metadataRefreshing = false;
      notifyListeners();
    }
  }

  Future<void> refresh() async {
    loading = true;
    error = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        api.get(ApiConstants.onboardingMeta),
        api.get(ApiConstants.onboardingDashboard),
        api.get(ApiConstants.onboardingPipeline),
      ]);
      meta = (results[0]['data'] as Map).cast<String, dynamic>();
      dashboard = (results[1]['data'] as Map).cast<String, dynamic>();
      pipeline = results[2]['data'] as List<dynamic>;
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> createRestaurant(Map<String, dynamic> payload) async {
    await api.post(ApiConstants.onboardingRestaurants, cleanPayload(payload));
    await refresh();
  }

  Future<void> createDriver(Map<String, dynamic> payload) async {
    await api.post(ApiConstants.onboardingDrivers, cleanPayload(payload));
    await refresh();
  }
}

