import 'dart:async';

import 'package:flutter/material.dart';

import '../models/device_status.dart';
import '../models/emergency_alert.dart';
import '../services/alert_websocket.dart';
import '../services/api_client.dart';
import '../services/session_storage.dart';
import 'emergency_screen.dart';
import 'history_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    super.key,
    required this.api,
    required this.userId,
    required this.userName,
    required this.onLogout,
  });

  final ApiClient api;
  final int userId;
  final String userName;
  final VoidCallback onLogout;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  DeviceStatus? _status;
  bool _loading = true;
  String? _error;
  bool _wsConnected = false;

  AlertWebSocket? _ws;
  EmergencyAlert? _activeAlert;
  int? _shownEmergencyEventId;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _initRealtime();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _ws?.disconnect();
    super.dispose();
  }

  Future<void> _initRealtime() async {
    await _refreshStatus(showLoader: true);
    _startWebSocket();
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      _refreshStatus(showLoader: false);
    });
  }

  void _startWebSocket() {
    _ws?.disconnect();

    _ws = AlertWebSocket(
      config: widget.api.config,
      userId: widget.userId,
      locationFallback: _status?.location ?? 'Perangkat',
    )
      ..onConnectionChanged = (connected) {
        if (!mounted) return;
        setState(() => _wsConnected = connected);
      }
      ..onTelemetry = _applyTelemetry
      ..onEmergency = _onEmergency
      ..onDismissed = _onDismissed
      ..connect();
  }

  void _applyTelemetry(Map<String, dynamic> payload) {
    if (!mounted || _status == null) return;

    final statusStr = payload['status'] as String?;
    final battery = payload['battery'] as int? ?? payload['battery_level'] as int?;

    setState(() {
      _status = _status!.copyWith(
        isOnline: payload['is_online'] as bool? ?? true,
        battery: battery ?? _status!.battery,
        lastStatus: statusStr ?? _status!.lastStatus,
        lastSeenAt: payload['last_seen_at'] as String? ?? _status!.lastSeenAt,
      );
    });

    if (statusStr == 'alarm' && _activeAlert == null) {
      _openAlarmFromStatus(_status!);
    }
  }

  void _onEmergency(EmergencyAlert alert) {
    if (_shownEmergencyEventId == alert.eventId) return;

    final enriched = EmergencyAlert(
      eventId: alert.eventId,
      alertType: alert.alertType,
      location: _status?.location ?? alert.location,
      magnitude: alert.magnitude,
      occurredAt: alert.occurredAt,
    );

    setState(() {
      _activeAlert = enriched;
      _shownEmergencyEventId = alert.eventId;
      if (_status != null) {
        _status = _status!.copyWith(lastStatus: 'alarm', isOnline: true);
      }
    });

    if (!mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (_) => EmergencyScreen(
          alert: enriched,
          api: widget.api,
          onClosed: () {
            setState(() {
              _activeAlert = null;
              _shownEmergencyEventId = null;
            });
          },
        ),
      ),
    );
  }

  void _onDismissed() {
    if (!mounted) return;

    setState(() {
      _activeAlert = null;
      _shownEmergencyEventId = null;
      if (_status != null) {
        _status = _status!.copyWith(lastStatus: 'normal');
      }
    });

    if (Navigator.of(context).canPop()) {
      Navigator.of(context).pop();
    }
  }

  Future<void> _refreshStatus({required bool showLoader}) async {
    if (showLoader) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    try {
      final status = await widget.api.fetchDeviceStatus();
      if (!mounted) return;

      final wasAlarm = _status?.isAlarm ?? false;
      setState(() => _status = status);

      if (status.isAlarm && !wasAlarm && _activeAlert == null) {
        await _openAlarmFromStatus(status);
      } else if (!status.isAlarm && _activeAlert != null) {
        _onDismissed();
      }
    } catch (e) {
      if (!mounted || !showLoader) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted && showLoader) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _openAlarmFromStatus(DeviceStatus status) async {
    try {
      final events = await widget.api.fetchHistory();
      final active = events.where((e) => e.isActive).toList();
      if (active.isEmpty) return;

      final latest = active.first;
      _onEmergency(
        EmergencyAlert(
          eventId: latest.id,
          alertType: latest.type == 'manual_sos' ? 'sos_active' : 'fall_detected',
          location: status.location,
          magnitude: latest.accelerationPeak,
          occurredAt: latest.occurredAt,
        ),
      );
    } catch (_) {}
  }

  Future<void> _logout() async {
    _pollTimer?.cancel();
    _ws?.disconnect();
    try {
      await widget.api.logout();
    } catch (_) {}
    await SessionStorage().clear();
    widget.onLogout();
  }

  IconData _batteryIcon(int? level) {
    if (level == null) return Icons.battery_unknown;
    if (level >= 80) return Icons.battery_full;
    if (level >= 50) return Icons.battery_5_bar;
    if (level >= 20) return Icons.battery_3_bar;
    return Icons.battery_alert;
  }

  @override
  Widget build(BuildContext context) {
    final status = _status;

    return Scaffold(
      appBar: AppBar(
        title: const Text('CareGuard'),
        actions: [
          IconButton(
            tooltip: 'Riwayat',
            icon: const Icon(Icons.history),
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => HistoryScreen(api: widget.api),
              ),
            ),
          ),
          IconButton(
            tooltip: 'Keluar',
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _refreshStatus(showLoader: true),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              'Halo, ${widget.userName}',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(
                  _wsConnected ? Icons.wifi : Icons.wifi_off,
                  size: 16,
                  color: _wsConnected ? Colors.green : Colors.grey,
                ),
                const SizedBox(width: 6),
                Text(
                  _wsConnected
                      ? 'WebSocket aktif — alarm real-time'
                      : 'WebSocket menyambung… (polling 5 detik)',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
            const SizedBox(height: 24),
            if (_loading && status == null)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(48),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_error != null)
              Card(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: () => _refreshStatus(showLoader: true),
                        child: const Text('Muat ulang'),
                      ),
                    ],
                  ),
                ),
              )
            else if (status != null) ...[
              _StatusCard(
                status: status,
                batteryIcon: _batteryIcon(status.battery),
              ),
              const SizedBox(height: 16),
              if (status.isAlarm)
                Card(
                  color: Colors.red.shade100,
                  child: ListTile(
                    leading: const Icon(Icons.notifications_active, color: Colors.red),
                    title: const Text(
                      'Alarm aktif',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    subtitle: const Text('Ketuk untuk buka layar penanganan.'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => _openAlarmFromStatus(status),
                  ),
                ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusCard extends StatelessWidget {
  const _StatusCard({required this.status, required this.batteryIcon});

  final DeviceStatus status;
  final IconData batteryIcon;

  @override
  Widget build(BuildContext context) {
    final online = status.isOnline;
    final alarm = status.isAlarm;

    return Card(
      elevation: alarm ? 4 : 2,
      color: alarm ? Colors.red.shade50 : null,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: alarm
            ? BorderSide(color: Colors.red.shade300, width: 2)
            : BorderSide.none,
      ),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Container(
              width: 88,
              height: 88,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: alarm
                    ? Colors.red.shade100
                    : online
                        ? Colors.green.shade100
                        : Colors.grey.shade200,
              ),
              child: Icon(
                alarm
                    ? Icons.notification_important
                    : online
                        ? Icons.sensors
                        : Icons.sensors_off,
                size: 48,
                color: alarm
                    ? Colors.red
                    : online
                        ? Colors.green
                        : Colors.grey,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              alarm
                  ? 'ALARM AKTIF'
                  : online
                      ? 'Perangkat Online'
                      : 'Perangkat Offline',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: alarm
                    ? Colors.red.shade800
                    : online
                        ? Colors.green.shade800
                        : Colors.grey.shade700,
              ),
            ),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(batteryIcon, size: 36, color: Colors.orange.shade800),
                const SizedBox(width: 12),
                Text(
                  status.battery != null ? '${status.battery}%' : '—',
                  style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 24),
            const Divider(),
            const SizedBox(height: 12),
            const Text('LOKASI', style: TextStyle(fontSize: 12, color: Colors.grey)),
            const SizedBox(height: 8),
            Text(
              status.location,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            Text(
              'Status: ${status.lastStatus}',
              style: TextStyle(
                color: alarm ? Colors.red.shade700 : Colors.grey.shade600,
                fontWeight: alarm ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
