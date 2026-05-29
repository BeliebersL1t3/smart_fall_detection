class CareEvent {
  CareEvent({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.status,
    this.accelerationPeak,
    this.notes,
    this.occurredAt,
    this.resolvedAt,
  });

  factory CareEvent.fromJson(Map<String, dynamic> json) {
    return CareEvent(
      id: json['id'] as int,
      type: json['type'] as String? ?? '',
      typeLabel: json['type_label'] as String? ?? '',
      status: json['status'] as String? ?? '',
      accelerationPeak: (json['acceleration_peak'] as num?)?.toDouble(),
      notes: json['notes'] as String?,
      occurredAt: json['occurred_at'] as String?,
      resolvedAt: json['resolved_at'] as String?,
    );
  }

  final int id;
  final String type;
  final String typeLabel;
  final String status;
  final double? accelerationPeak;
  final String? notes;
  final String? occurredAt;
  final String? resolvedAt;

  bool get isActive => status == 'pending' || status == 'confirmed';
}
