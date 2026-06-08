class DeviceStatus {
  DeviceStatus({
    required this.deviceId,
    required this.isOnline,
    required this.battery,
    required this.location,
    required this.lastStatus,
    this.lastSeenAt,
  });

  factory DeviceStatus.fromJson(Map<String, dynamic> json) {
    return DeviceStatus(
      deviceId: json['device_id'] as int? ?? 0,
      isOnline: json['is_online'] as bool? ?? false,
      battery: json['battery'] as int?,
      location: json['location'] as String? ?? '—',
      lastStatus: json['last_status'] as String? ?? 'normal',
      lastSeenAt: json['last_seen_at'] as String?,
    );
  }

  final int deviceId;
  final bool isOnline;
  final int? battery;
  final String location;
  final String lastStatus;
  final String? lastSeenAt;

  bool get isAlarm => lastStatus == 'alarm';

  DeviceStatus copyWith({
    int? deviceId,
    bool? isOnline,
    int? battery,
    String? location,
    String? lastStatus,
    String? lastSeenAt,
  }) {
    return DeviceStatus(
      deviceId: deviceId ?? this.deviceId,
      isOnline: isOnline ?? this.isOnline,
      battery: battery ?? this.battery,
      location: location ?? this.location,
      lastStatus: lastStatus ?? this.lastStatus,
      lastSeenAt: lastSeenAt ?? this.lastSeenAt,
    );
  }
}
