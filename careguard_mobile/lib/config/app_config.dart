/// Ubah IP/host sesuai lingkungan Anda.
///
/// - Emulator Android: API biasanya `http://10.0.2.2:8000/api`
/// - HP fisik (Wi‑Fi sama): `http://192.168.x.x:8000/api` (IP PC Laragon)
/// - WebSocket: port dari `IOT_WS_PORT` di `.env` Laravel (default 6001)
class AppConfig {
  AppConfig({
    required this.apiBaseUrl,
    required this.wsHost,
    required this.wsPort,
  });

  /// Contoh: http://192.168.1.5:8000/api
  final String apiBaseUrl;

  /// Host WebSocket (bukan /api) — sama LAN dengan HP
  final String wsHost;
  final int wsPort;

  String get loginUrl => '$apiBaseUrl/auth/login';
  String get meUrl => '$apiBaseUrl/auth/me';
  String get logoutUrl => '$apiBaseUrl/auth/logout';
  String get deviceStatusUrl => '$apiBaseUrl/device/status';
  String get eventsHistoryUrl => '$apiBaseUrl/events/history';

  String resolveEventUrl(int eventId) =>
      '$apiBaseUrl/events/$eventId/resolve';

  String wsUrl(int userId) => 'ws://$wsHost:$wsPort?user_id=$userId';

  static AppConfig defaults() => AppConfig(
        apiBaseUrl: 'http://10.0.2.2:8000/api',
        wsHost: '10.0.2.2',
        wsPort: 6001,
      );

  AppConfig copyWith({
    String? apiBaseUrl,
    String? wsHost,
    int? wsPort,
  }) {
    return AppConfig(
      apiBaseUrl: apiBaseUrl ?? this.apiBaseUrl,
      wsHost: wsHost ?? this.wsHost,
      wsPort: wsPort ?? this.wsPort,
    );
  }
}
