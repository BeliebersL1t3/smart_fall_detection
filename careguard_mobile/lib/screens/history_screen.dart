import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/care_event.dart';
import '../services/api_client.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<CareEvent> _events = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final events = await widget.api.fetchHistory();
      if (!mounted) return;
      setState(() => _events = events);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _formatDate(String? iso) {
    if (iso == null) return '—';
    try {
      final dt = DateTime.parse(iso).toLocal();
      return DateFormat('d MMM yyyy, HH:mm').format(dt);
    } catch (_) {
      return iso;
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
      case 'confirmed':
        return Colors.red;
      case 'false_alarm':
        return Colors.orange;
      case 'resolved_by_caregiver':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Kejadian'),
        actions: [
          IconButton(onPressed: _load, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 12),
                      FilledButton(onPressed: _load, child: const Text('Coba lagi')),
                    ],
                  ),
                )
              : _events.isEmpty
                  ? const Center(child: Text('Belum ada riwayat.'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _events.length,
                        itemBuilder: (context, index) {
                          final e = _events[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          e.typeLabel,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                          ),
                                        ),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 8,
                                          vertical: 4,
                                        ),
                                        decoration: BoxDecoration(
                                          color: _statusColor(e.status)
                                              .withValues(alpha: 0.15),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          e.status,
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: _statusColor(e.status),
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  Row(
                                    children: [
                                      const Icon(Icons.schedule, size: 16, color: Colors.grey),
                                      const SizedBox(width: 6),
                                      Text(
                                        _formatDate(e.occurredAt),
                                        style: const TextStyle(color: Colors.grey),
                                      ),
                                    ],
                                  ),
                                  if (e.notes != null && e.notes!.isNotEmpty) ...[
                                    const SizedBox(height: 12),
                                    const Text(
                                      'Catatan perawat:',
                                      style: TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 12,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(e.notes!),
                                  ],
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}
