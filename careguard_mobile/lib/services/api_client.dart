import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/app_config.dart';
import '../models/care_event.dart';
import '../models/device_status.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required this.config, this.token});

  AppConfig config;
  String? token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse(config.loginUrl),
      headers: _headers,
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': 'careguard_mobile',
      }),
    );

    return _decode(response);
  }

  Future<Map<String, dynamic>> me() async {
    final response = await http.get(
      Uri.parse(config.meUrl),
      headers: _headers,
    );
    return _decode(response);
  }

  Future<void> logout() async {
    final response = await http.post(
      Uri.parse(config.logoutUrl),
      headers: _headers,
    );
    if (response.statusCode >= 400 && response.statusCode != 401) {
      _decode(response);
    }
  }

  Future<DeviceStatus> fetchDeviceStatus() async {
    final data = await _get(config.deviceStatusUrl);
    return DeviceStatus.fromJson(data);
  }

  Future<List<CareEvent>> fetchHistory() async {
    final data = await _get(config.eventsHistoryUrl);
    final list = data['events'] as List<dynamic>? ?? [];
    return list
        .map((e) => CareEvent.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> resolveEvent({
    required int eventId,
    required String status,
    String? notes,
  }) async {
    final response = await http.post(
      Uri.parse(config.resolveEventUrl(eventId)),
      headers: _headers,
      body: jsonEncode({
        'status': status,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      }),
    );
    _decode(response);
  }

  Future<Map<String, dynamic>> _get(String url) async {
    final response = await http.get(Uri.parse(url), headers: _headers);
    return _decode(response);
  }

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic>? body;
    try {
      if (response.body.isNotEmpty) {
        body = jsonDecode(response.body) as Map<String, dynamic>;
      }
    } catch (_) {
      body = null;
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body ?? {};
    }

    final message = body?['message'] as String? ??
        (body?['errors'] is Map
            ? (body!['errors'] as Map).values.first?.first?.toString()
            : null) ??
        'Request gagal (${response.statusCode})';

    throw ApiException(message, statusCode: response.statusCode);
  }
}
