import 'package:flutter/material.dart';

import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'services/api_client.dart';
import 'services/session_storage.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const CareGuardApp());
}

class CareGuardApp extends StatefulWidget {
  const CareGuardApp({super.key});

  @override
  State<CareGuardApp> createState() => _CareGuardAppState();
}

class _CareGuardAppState extends State<CareGuardApp> {
  final _storage = SessionStorage();
  bool _booting = true;
  bool _loggedIn = false;

  ApiClient? _api;
  int? _userId;
  String _userName = '';

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final token = await _storage.getToken();
    final userId = await _storage.getUserId();
    final userName = await _storage.getUserName();
    final config = await _storage.loadConfig();

    if (token != null && userId != null) {
      final api = ApiClient(config: config, token: token);
      try {
        await api.me();
        _api = api;
        _userId = userId;
        _userName = userName ?? 'Perawat';
        _loggedIn = true;
      } catch (_) {
        await _storage.clear();
      }
    }

    if (mounted) {
      setState(() => _booting = false);
    }
  }

  void _onLoggedIn() {
    _bootstrap().then((_) {
      if (mounted) setState(() => _loggedIn = _api != null);
    });
  }

  void _onLogout() {
    setState(() {
      _loggedIn = false;
      _api = null;
      _userId = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CareGuard',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF4F46E5)),
        useMaterial3: true,
      ),
      home: _booting
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : _loggedIn && _api != null && _userId != null
              ? HomeScreen(
                  api: _api!,
                  userId: _userId!,
                  userName: _userName,
                  onLogout: _onLogout,
                )
              : LoginScreen(onLoggedIn: _onLoggedIn),
    );
  }
}
