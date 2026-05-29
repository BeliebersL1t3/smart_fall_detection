class EmergencyAlert {
  EmergencyAlert({
    required this.eventId,
    required this.alertType,
    required this.location,
    this.magnitude,
    this.occurredAt,
  });

  final int eventId;
  final String alertType;
  final String location;
  final double? magnitude;
  final String? occurredAt;

  String get title {
    switch (alertType) {
      case 'sos_active':
        return 'SOS MANUAL';
      case 'fall_detected':
        return 'JATUH TERDETEKSI';
      default:
        return 'PERINGATAN DARURAT';
    }
  }
}
