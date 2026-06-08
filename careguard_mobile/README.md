# CareGuard Mobile (Flutter)

Aplikasi perawat untuk monitoring ESP32 — memanggil API Laravel (Babak 1) + WebSocket alarm real-time.

## Persiapan

1. Install [Flutter SDK](https://docs.flutter.dev/get-started/install).
2. Di folder ini, generate folder platform (sekali saja):

```bash
cd careguard_mobile
flutter create . --project-name careguard_mobile
flutter pub get
```

3. Di folder `api` Laravel, jalankan WebSocket:

```bash
npm run ws
```

4. Pastikan akun login sudah punya perangkat di database (sama seperti dashboard web).

## Menjalankan

```bash
flutter run
```

### URL server

Di layar login, buka **Pengaturan server**:

| Lingkungan | API Base URL | WS Host |
|------------|--------------|---------|
| Emulator Android | `http://10.0.2.2:8000/api` | `10.0.2.2` |
| HP fisik (Wi‑Fi sama dengan PC) | `http://IP-PC:8000/api` | IP PC yang sama |
| Laragon virtual host | `http://smart_fall_detection.test/api` | IP PC (bukan hostname .test dari HP) |

Port WebSocket default: **6001** (`IOT_WS_PORT` di `.env` Laravel).

### Android — HTTP (non-HTTPS)

Setelah `flutter create`, edit `android/app/src/main/AndroidManifest.xml` pada tag `<application>`:

```xml
android:usesCleartextTraffic="true"
```

## Fitur

- **Login** — `POST /api/auth/login`, token disimpan di SharedPreferences
- **Home** — status online, baterai, lokasi (`GET /api/device/status`)
- **WebSocket** — layar merah saat `fall_detected` / `sos_active`
- **Emergency** — False Alarm / Tangani Pasien + catatan → `POST /api/events/{id}/resolve`
- **Riwayat** — `GET /api/events/history`

## Struktur

```
lib/
  config/app_config.dart
  models/
  services/   api_client, session_storage, alert_websocket
  screens/    login, home, emergency, history
  main.dart
```
