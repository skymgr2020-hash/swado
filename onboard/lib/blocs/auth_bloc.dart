import 'dart:async';

import 'package:flutter/foundation.dart';

import '../models/session.dart';
import '../services/auth_service.dart';

class AuthBloc extends ChangeNotifier {
  AuthBloc({AuthService? authService})
    : _authService = authService ?? AuthService();

  final AuthService _authService;

  Session? session;
  bool booting = true;
  bool loading = false;
  String? error;

  bool get isAuthenticated => session != null;

  Future<void> restore() async {
    booting = true;
    error = null;
    notifyListeners();

    try {
      session = await _authService.restore();
      booting = false;
      notifyListeners();

      if (session != null) {
        unawaited(refreshSession(clearOnFailure: true));
      }
    } catch (e) {
      await _authService.clear();
      session = null;
      error = e.toString();
      booting = false;
      notifyListeners();
    }
  }

  Future<void> login({
    required String baseUrl,
    required String email,
    required String password,
    required String role,
  }) async {
    loading = true;
    error = null;
    notifyListeners();

    try {
      session = await _authService.login(
        baseUrl: baseUrl,
        email: email,
        password: password,
        role: role,
      );
    } catch (e) {
      error = e.toString();
      rethrow;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<bool> refreshSession({bool clearOnFailure = false}) async {
    final current = session;
    if (current == null) return false;

    try {
      session = await _authService.getCurrentUser(current);
      error = null;
      notifyListeners();
      return true;
    } catch (e) {
      error = e.toString();
      if (clearOnFailure) {
        await _authService.clear();
        session = null;
      }
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    loading = true;
    notifyListeners();

    try {
      await _authService.logout(session);
    } finally {
      session = null;
      loading = false;
      notifyListeners();
    }
  }
}
