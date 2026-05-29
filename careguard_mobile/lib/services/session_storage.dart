import 'package:shared_preferences/shared_preferences.dart';

import '../config/app_config.dart';

class SessionStorage {
  static const _keyToken = 'auth_token';
  static const _keyUserId = 'user_id';
  static const _keyUserName = 'user_name';
  static const _keyApiBase = 'api_base_url';
  static const _keyWsHost = 'ws_host';
  static const _keyWsPort = 'ws_port';

  Future<void> saveSession({
    required String token,
    required int userId,
    required String userName,
    required AppConfig config,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyToken, token);
    await prefs.setInt(_keyUserId, userId);
    await prefs.setString(_keyUserName, userName);
    await prefs.setString(_keyApiBase, config.apiBaseUrl);
    await prefs.setString(_keyWsHost, config.wsHost);
    await prefs.setInt(_keyWsPort, config.wsPort);
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyToken);
    await prefs.remove(_keyUserId);
    await prefs.remove(_keyUserName);
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyToken);
  }

  Future<int?> getUserId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_keyUserId);
  }

  Future<String?> getUserName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyUserName);
  }

  Future<AppConfig> loadConfig() async {
    final prefs = await SharedPreferences.getInstance();
    final defaults = AppConfig.defaults();
    return AppConfig(
      apiBaseUrl: prefs.getString(_keyApiBase) ?? defaults.apiBaseUrl,
      wsHost: prefs.getString(_keyWsHost) ?? defaults.wsHost,
      wsPort: prefs.getInt(_keyWsPort) ?? defaults.wsPort,
    );
  }

  Future<void> saveConfig(AppConfig config) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyApiBase, config.apiBaseUrl);
    await prefs.setString(_keyWsHost, config.wsHost);
    await prefs.setInt(_keyWsPort, config.wsPort);
  }
}
