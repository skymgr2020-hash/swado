import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../config/api_constants.dart';
import '../models/api_exception.dart';
import '../models/session.dart';

class AuthService {
  static const _tokenKeys = ['onboard_auth_token', 'auth_token', 'token'];
  static const _sessionKey = 'onboard_session';
  static const _baseUrlKey = 'baseUrl';

  Future<Session?> restore() async {
    final prefs = await SharedPreferences.getInstance();
    final token = await getToken();
    if (token == null || token.isEmpty) return null;

    final cached = prefs.getString(_sessionKey);
    if (cached != null && cached.isNotEmpty) {
      try {
        final session = Session.fromJson(jsonDecode(cached));
        if (session.token.isNotEmpty) return session;
      } catch (_) {
        await prefs.remove(_sessionKey);
      }
    }

    final legacyBase = prefs.getString(_baseUrlKey) ?? ApiConstants.baseUrl;
    return Session(
      token: token,
      baseUrl: legacyBase,
      name: prefs.getString('name') ?? 'Operator',
      role: prefs.getString('role') ?? 'admin',
    );
  }

  Future<Session> login({
    required String baseUrl,
    required String email,
    required String password,
    required String role,
  }) async {
    final root = normalizeBaseUrl(baseUrl);
    final data = await _post(
      baseUrl: root,
      path: ApiConstants.login,
      body: {'email': email.trim(), 'password': password, 'role': role},
    );

    final payload = _dataMap(data);
    final user = _map(payload['user']);
    final token = payload['token']?.toString() ?? '';
    if (token.isEmpty || user.isEmpty) {
      throw ApiException('Login response was missing session data.');
    }

    final session = Session.fromAuthPayload(
      token: token,
      baseUrl: root,
      user: user,
      requestedRole: role,
    );
    _assertOnboardRole(session);
    await persist(session);
    return session;
  }

  Future<Session> getCurrentUser(Session session) async {
    final data = await _get(
      baseUrl: session.baseUrl,
      path: ApiConstants.currentUser,
      token: session.token,
    );
    final user = _dataMap(data);
    final refreshed = Session.fromAuthPayload(
      token: session.token,
      baseUrl: session.baseUrl,
      user: user,
      requestedRole: session.role,
    );
    _assertOnboardRole(refreshed);
    await persist(refreshed);
    return refreshed;
  }

  Future<void> logout(Session? session) async {
    if (session != null && session.token.isNotEmpty) {
      try {
        await _post(
          baseUrl: session.baseUrl,
          path: ApiConstants.logout,
          token: session.token,
          body: const {},
        );
      } catch (_) {
        // Local cleanup should still happen when the server token is already dead.
      }
    }
    await clear();
  }

  Future<void> persist(Session session) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_sessionKey, jsonEncode(session.toJson()));
    await prefs.setString(_baseUrlKey, session.baseUrl);
    await prefs.setString('name', session.name);
    await prefs.setString('role', session.role);
    for (final key in _tokenKeys) {
      await prefs.setString(key, session.token);
    }
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    for (final key in _tokenKeys) {
      final token = prefs.getString(key);
      if (token != null && token.isNotEmpty) return token;
    }
    return null;
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_sessionKey);
    await prefs.remove(_baseUrlKey);
    await prefs.remove('name');
    await prefs.remove('role');
    for (final key in _tokenKeys) {
      await prefs.remove(key);
    }
  }

  String normalizeBaseUrl(String value) => ApiConstants.normalizeBaseUrl(value);

  Future<Map<String, dynamic>> _get({
    required String baseUrl,
    required String path,
    required String token,
  }) async {
    final res = await http.get(_uri(baseUrl, path), headers: _headers(token));
    return _decode(res);
  }

  Future<Map<String, dynamic>> _post({
    required String baseUrl,
    required String path,
    required Map<String, dynamic> body,
    String? token,
  }) async {
    final res = await http.post(
      _uri(baseUrl, path),
      headers: _headers(token),
      body: jsonEncode(body),
    );
    return _decode(res);
  }

  Uri _uri(String baseUrl, String path) =>
      Uri.parse('${normalizeBaseUrl(baseUrl)}$path');

  Map<String, String> _headers(String? token) => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
  };

  Map<String, dynamic> _decode(http.Response res) {
    final text = res.body.trim();
    if (text.isEmpty) {
      if (res.statusCode >= 200 && res.statusCode < 300) {
        return {'success': true};
      }
      throw ApiException('Empty response from server.');
    }

    if (text.startsWith('<!DOCTYPE html>') || text.startsWith('<html')) {
      throw ApiException(
        'Server returned HTML instead of JSON. Check the API base URL.',
      );
    }

    final decoded = jsonDecode(text);
    final data = decoded is Map<String, dynamic>
        ? decoded
        : Map<String, dynamic>.from(decoded as Map);

    if (res.statusCode >= 200 && res.statusCode < 300) return data;

    if (res.statusCode == 401) {
      throw ApiException('Session expired. Please sign in again.');
    }

    throw ApiException(
      _messageFrom(data, fallback: 'Request failed (${res.statusCode})'),
    );
  }

  Map<String, dynamic> _dataMap(Map<String, dynamic> response) {
    final data = response['data'];
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return const {};
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const {};
  }

  String _messageFrom(Map<String, dynamic> data, {required String fallback}) {
    if (data['message'] != null) return data['message'].toString();
    final errors = data['errors'];
    if (errors is Map) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) {
          return value.first.toString();
        }
        if (value != null && value.toString().trim().isNotEmpty) {
          return value.toString();
        }
      }
    }
    return fallback;
  }

  void _assertOnboardRole(Session session) {
    if (session.canUseOnboardApp) return;
    throw ApiException(
      'This account is not allowed to use the onboarding console.',
    );
  }
}
