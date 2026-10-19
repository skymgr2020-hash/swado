import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_constants.dart';
import '../models/api_exception.dart';
import '../models/session.dart';

class ApiClient {
  ApiClient(this.session);

  final Session session;

  Uri uri(String path) =>
      Uri.parse('${ApiConstants.normalizeBaseUrl(session.baseUrl)}$path');

  Map<String, String> get headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${session.token}',
  };

  Future<Map<String, dynamic>> get(String path) async {
    return _decode(await http.get(uri(path), headers: headers));
  }

  Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body,
  ) async {
    return _decode(
      await http.post(uri(path), headers: headers, body: jsonEncode(body)),
    );
  }

  Map<String, dynamic> _decode(http.Response res) {
    final map = res.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(
        map['message']?.toString() ?? 'Request failed (${res.statusCode})',
      );
    }

    return map;
  }
}
