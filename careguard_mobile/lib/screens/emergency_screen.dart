import 'package:flutter/material.dart';

import '../models/emergency_alert.dart';
import '../services/api_client.dart';

class EmergencyScreen extends StatefulWidget {
  const EmergencyScreen({
    super.key,
    required this.alert,
    required this.api,
    required this.onClosed,
  });

  final EmergencyAlert alert;
  final ApiClient api;
  final VoidCallback onClosed;

  @override
  State<EmergencyScreen> createState() => _EmergencyScreenState();
}

class _EmergencyScreenState extends State<EmergencyScreen> {
  bool _submitting = false;

  Future<void> _resolve(String status, {String? notes}) async {
    setState(() => _submitting = true);
    try {
      await widget.api.resolveEvent(
        eventId: widget.alert.eventId,
        status: status,
        notes: notes,
      );
      if (!mounted) return;
      widget.onClosed();
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _showResolveDialog() async {
    final notesCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final submitted = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        title: const Text('Catatan penanganan'),
        content: Form(
          key: formKey,
          child: TextFormField(
            controller: notesCtrl,
            maxLines: 4,
            decoration: const InputDecoration(
              hintText: 'Contoh: Pasien terpeleset, sudah dibantu berdiri...',
              border: OutlineInputBorder(),
            ),
            validator: (v) =>
                v == null || v.trim().isEmpty ? 'Catatan wajib diisi' : null,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              if (formKey.currentState!.validate()) {
                Navigator.pop(ctx, true);
              }
            },
            child: const Text('Selesai'),
          ),
        ],
      ),
    );

    if (submitted == true) {
      await _resolve('resolved', notes: notesCtrl.text.trim());
    }
    notesCtrl.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final alert = widget.alert;

    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: const Color(0xFFB91C1C),
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Icon(Icons.warning_amber_rounded,
                    color: Colors.white, size: 72),
                const SizedBox(height: 16),
                Text(
                  alert.title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.2,
                  ),
                ),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'LOKASI',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        alert.location,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      if (alert.magnitude != null) ...[
                        const SizedBox(height: 16),
                        Text(
                          'Puncak: ${alert.magnitude!.toStringAsFixed(2)} G',
                          style: const TextStyle(color: Colors.white),
                        ),
                      ],
                    ],
                  ),
                ),
                const Spacer(),
                if (_submitting)
                  const Center(
                    child: CircularProgressIndicator(color: Colors.white),
                  )
                else ...[
                  SizedBox(
                    height: 56,
                    child: OutlinedButton(
                      onPressed: () => _resolve('false_alarm'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.white,
                        side: const BorderSide(color: Colors.white, width: 2),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text(
                        'Tandai False Alarm',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    height: 56,
                    child: FilledButton(
                      onPressed: _showResolveDialog,
                      style: FilledButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: const Color(0xFFB91C1C),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text(
                        'Tangani Pasien',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
