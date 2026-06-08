import 'dart:async';
import 'dart:convert';

import 'package:web_socket_channel/web_socket_channel.dart';

import '../config/app_config.dart';
import '../models/emergency_alert.dart';

typedef AlertHandler = void Function(EmergencyAlert alert);
typedef VoidHandler = void Function();
typedef TelemetryHandler = void Function(Map<String, dynamic> payload);
typedef ConnectionHandler = void Function(bool connected);

class AlertWebSocket {
  AlertWebSocket({
    required this.config,
    required this.userId,
    required this.locationFallback,
  });

  final AppConfig config;
  final int userId;
  final String locationFallback;

  WebSocketChannel? _channel;
  StreamSubscription? _subscription;
  Timer? _reconnectTimer;
  bool _connected = false;
  bool _intentionalDisconnect = false;

  AlertHandler? onEmergency;
  VoidHandler? onDismissed;
  TelemetryHandler? onTelemetry;
  ConnectionHandler? onConnectionChanged;

  bool get isConnected => _connected;

  void connect() {
    if (_connected && _channel != null) return;

    _intentionalDisconnect = false;
    _reconnectTimer?.cancel();
    _reconnectTimer = null;

    try {
      _channel?.sink.close();
      _channel = WebSocketChannel.connect(Uri.parse(config.wsUrl(userId)));
      _subscription = _channel!.stream.listen(
        _onMessage,
        onError: (_) => _markDisconnected(),
        onDone: () => _markDisconnected(),
        cancelOnError: false,
      );
    } catch (_) {
      _markDisconnected();
    }
  }

  void disconnect() {
    _intentionalDisconnect = true;
    _reconnectTimer?.cancel();
    _reconnectTimer = null;
    _subscription?.cancel();
    _subscription = null;
    _channel?.sink.close();
    _channel = null;
    _setConnected(false);
  }

  void _markDisconnected() {
    _subscription?.cancel();
    _subscription = null;
    _channel = null;
    _setConnected(false);

    if (!_intentionalDisconnect) {
      _scheduleReconnect();
    }
  }

  void _scheduleReconnect() {
    if (_reconnectTimer != null || _intentionalDisconnect) return;
    _reconnectTimer = Timer(const Duration(seconds: 3), () {
      _reconnectTimer = null;
      connect();
    });
  }

  void _setConnected(bool value) {
    if (_connected == value) return;
    _connected = value;
    onConnectionChanged?.call(value);
  }

  void _onMessage(dynamic raw) {
    try {
      final msg = jsonDecode(raw as String) as Map<String, dynamic>;
      final type = msg['type'] as String?;
      final payload = msg['payload'] as Map<String, dynamic>? ?? {};

      if (type == 'connected') {
        _setConnected(true);
        return;
      }

      if (!_connected) {
        _setConnected(true);
      }

      if (type == 'telemetry' && payload.isNotEmpty) {
        onTelemetry?.call(payload);
      }

      if (type == 'fall_detected' || type == 'sos_active') {
        final eventId = payload['event_id'] as int?;
        if (eventId == null) return;

        onEmergency?.call(
          EmergencyAlert(
            eventId: eventId,
            alertType: type!,
            location: locationFallback,
            magnitude: (payload['magnitude'] as num?)?.toDouble(),
            occurredAt: payload['occurred_at'] as String?,
          ),
        );
      }

      if (type == 'alarm_dismissed' || type == 'sos_cancelled') {
        onDismissed?.call();
      }
    } catch (_) {
      // ignore malformed frames
    }
  }
}
