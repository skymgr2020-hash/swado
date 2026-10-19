import 'package:flutter/material.dart';

void showToast(BuildContext context, String message) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}

String? requiredText(String? value) =>
    value == null || value.trim().isEmpty ? 'Required' : null;

Map<String, dynamic> cleanPayload(Map<String, dynamic> input) {
  final out = <String, dynamic>{};
  input.forEach((key, value) {
    if (value == null) return;
    if (value is String && value.trim().isEmpty) return;
    if (value is List && value.isEmpty) return;
    out[key] = value;
  });
  return out;
}
