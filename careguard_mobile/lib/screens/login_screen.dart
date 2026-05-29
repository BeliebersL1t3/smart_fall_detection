import 'package:flutter/material.dart';

import '../config/app_config.dart';
import '../services/api_client.dart';
import '../services/session_storage.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.onLoggedIn});

  final VoidCallback onLoggedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _apiBaseCtrl = TextEditingController();
  final _wsHostCtrl = TextEditingController();
  final _wsPortCtrl = TextEditingController(text: '6001');

  final _storage = SessionStorage();
  bool _loading = false;
  bool _showAdvanced = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadSavedConfig();
  }

  Future<void> _loadSavedConfig() async {
    final config = await _storage.loadConfig();
    _apiBaseCtrl.text = config.apiBaseUrl;
    _wsHostCtrl.text = config.wsHost;
    _wsPortCtrl.text = '${config.wsPort}';
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    _apiBaseCtrl.dispose();
    _wsHostCtrl.dispose();
    _wsPortCtrl.dispose();
    super.dispose();
  }

  AppConfig _buildConfig() {
    return AppConfig(
      apiBaseUrl: _apiBaseCtrl.text.trim().replaceAll(RegExp(r'/+$'), ''),
      wsHost: _wsHostCtrl.text.trim(),
      wsPort: int.tryParse(_wsPortCtrl.text.trim()) ?? 6001,
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    final config = _buildConfig();
    await _storage.saveConfig(config);

    final api = ApiClient(config: config);

    try {
      final loginData = await api.login(
        email: _emailCtrl.text.trim(),
        password: _passwordCtrl.text,
      );

      final token = loginData['token'] as String;
      api.token = token;

      final meData = await api.me();
      final user = meData['user'] as Map<String, dynamic>;
      final userId = user['id'] as int;

      await _storage.saveSession(
        token: token,
        userId: userId,
        userName: user['name'] as String? ?? 'Perawat',
        config: config,
      );

      if (!mounted) return;
      widget.onLoggedIn();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = 'Tidak dapat terhubung ke server. Periksa URL API & jaringan.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Icon(Icons.health_and_safety,
                        size: 64, color: Theme.of(context).colorScheme.primary),
                    const SizedBox(height: 12),
                    Text(
                      'CareGuard',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Login perawat',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.grey,
                          ),
                    ),
                    const SizedBox(height: 32),
                    TextFormField(
                      controller: _emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        prefixIcon: Icon(Icons.email_outlined),
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Email wajib diisi' : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _passwordCtrl,
                      obscureText: true,
                      decoration: const InputDecoration(
                        labelText: 'Password',
                        prefixIcon: Icon(Icons.lock_outline),
                        border: OutlineInputBorder(),
                      ),
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Password wajib diisi' : null,
                    ),
                    const SizedBox(height: 8),
                    TextButton.icon(
                      onPressed: () =>
                          setState(() => _showAdvanced = !_showAdvanced),
                      icon: Icon(_showAdvanced
                          ? Icons.expand_less
                          : Icons.expand_more),
                      label: const Text('Pengaturan server (API & WebSocket)'),
                    ),
                    if (_showAdvanced) ...[
                      TextFormField(
                        controller: _apiBaseCtrl,
                        decoration: const InputDecoration(
                          labelText: 'API Base URL',
                          hintText: 'http://192.168.1.5:8000/api',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            flex: 2,
                            child: TextFormField(
                              controller: _wsHostCtrl,
                              decoration: const InputDecoration(
                                labelText: 'WS Host',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _wsPortCtrl,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Port',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Jalankan di PC: npm run ws (folder api). HP & PC harus satu Wi‑Fi.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.grey,
                            ),
                      ),
                    ],
                    if (_error != null) ...[
                      const SizedBox(height: 16),
                      Text(
                        _error!,
                        style: const TextStyle(color: Colors.red),
                        textAlign: TextAlign.center,
                      ),
                    ],
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _loading ? null : _submit,
                      style: FilledButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: _loading
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Masuk'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
