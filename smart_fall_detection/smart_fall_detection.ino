#include <Wire.h>
#include <math.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ================= WiFi =================
const char* WIFI_SSID     = "KONTRAKAN BARU";
const char* WIFI_PASSWORD = "kuncipintu";

// ================= Laravel API =================
const char* API_BASE_URL  = "http://192.168.1.5:8000";   // ganti IP server Laravel kamu
const char* DEVICE_TOKEN  = "TOKEN_RAHASIA_123";             // dari Settings / Dashboard CareGuard

// Endpoint
const char* ENDPOINT_SENSOR  = "/api/sensor-data";    // kirim data MAG rutin
const char* ENDPOINT_FALL     = "/api/fall-detected";  // kirim saat jatuh terdeteksi
const char* ENDPOINT_SOS      = "/api/sos";            // kirim saat tombol SOS ditekan

// ================= PIN =================
#define SDA_PIN    22
#define SCL_PIN    23
#define BUZZER_PIN 33
#define SOS_BUTTON 25

// ================= FALL DETECTION =================
float impactThreshold   = 2.8;
float movementThreshold = 1.2;
unsigned long immobilityTime = 3000;

bool impactDetected = false;
bool alarmActive    = false;
unsigned long impactTime = 0;

// ================= BUTTON =================
int lastButtonState = HIGH;

// ================= BATERAI =================
// Ganti dengan pembacaan ADC/voltage divider jika sudah di-wire
int readBatteryPercent() {
  // Contoh: map analogRead(BATTERY_PIN) ke 0-100
  return 78; // placeholder — sesuaikan dengan hardware
}

// ================= INTERVAL KIRIM DATA =================
unsigned long lastSendTime = 0;
const unsigned long sendInterval = 2000; // kirim data sensor tiap 2 detik

// ================================================
// FUNGSI: Koneksi WiFi
// ================================================
void connectWiFi() {
  Serial.print("Connecting to WiFi");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 20) {
    delay(500);
    Serial.print(".");
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n❌ WiFi GAGAL! Cek SSID/Password.");
  }
}

// ================================================
// FUNGSI: Kirim data sensor (rutin)
// ================================================
void sendSensorData(float mag, float ax, float ay, float az) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_SENSOR;
  http.begin(url);

  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.addHeader("Accept", "application/json");

  // Buat JSON payload
  StaticJsonDocument<200> doc;
  doc["magnitude"] = mag;
  doc["ax"]        = ax;
  doc["ay"]        = ay;
  doc["az"]        = az;
  doc["status"]    = alarmActive ? "alarm" : "normal";
  doc["battery"]   = readBatteryPercent();

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200 || httpCode == 201) {
    Serial.println("📡 Data sensor terkirim ke Laravel");
  } else {
    Serial.printf("⚠️  Gagal kirim sensor, HTTP: %d\n", httpCode);
  }

  http.end();
}

// ================================================
// FUNGSI: Kirim notifikasi FALL DETECTED
// ================================================
void sendFallAlert(float mag) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_FALL;
  http.begin(url);

  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<200> doc;
  doc["event"]     = "fall_detected";
  doc["magnitude"] = mag;
  doc["message"]   = "Pengguna terdeteksi jatuh!";

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200 || httpCode == 201) {
    Serial.println("🆘 Fall alert terkirim ke Laravel!");
  } else {
    Serial.printf("⚠️  Gagal kirim fall alert, HTTP: %d\n", httpCode);
  }

  http.end();
}

// ================================================
// FUNGSI: Kirim notifikasi SOS
// ================================================
void sendSOSAlert(bool isActive) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_SOS;
  http.begin(url);

  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<200> doc;
  doc["event"]   = isActive ? "sos_active" : "sos_cancelled";
  doc["message"] = isActive ? "Tombol SOS ditekan!" : "Alarm SOS dibatalkan";

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200 || httpCode == 201) {
    Serial.println("📲 SOS alert terkirim ke Laravel!");
  } else {
    Serial.printf("⚠️  Gagal kirim SOS, HTTP: %d\n", httpCode);
  }

  http.end();
}

// ================= SETUP =================
void setup() {
  Serial.begin(115200);
  delay(1000);

  // I2C MPU6050
  Wire.begin(SDA_PIN, SCL_PIN);

  // BUZZER
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  // BUTTON
  pinMode(SOS_BUTTON, INPUT_PULLUP);

  // WAKE MPU6050
  Wire.beginTransmission(0x68);
  Wire.write(0x6B);
  Wire.write(0);
  Wire.endTransmission(true);

  Serial.println("=================================");
  Serial.println(" SMART FALL DETECTION SYSTEM    ");
  Serial.println("=================================");

  // Koneksi WiFi
  connectWiFi();

  Serial.println("System Ready...");
}

// ================= LOOP =================
void loop() {

  // Reconnect WiFi jika putus
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️  WiFi putus, reconnecting...");
    connectWiFi();
  }

  // =========================================
  // READ MPU6050
  // =========================================
  Wire.beginTransmission(0x68);
  Wire.write(0x3B);
  Wire.endTransmission(false);
  Wire.requestFrom(0x68, 6, true);

  int16_t ax_raw = Wire.read() << 8 | Wire.read();
  int16_t ay_raw = Wire.read() << 8 | Wire.read();
  int16_t az_raw = Wire.read() << 8 | Wire.read();

  float ax_g = ax_raw / 16384.0;
  float ay_g = ay_raw / 16384.0;
  float az_g = az_raw / 16384.0;

  float magnitude = sqrt(
    (ax_g * ax_g) +
    (ay_g * ay_g) +
    (az_g * az_g)
  );

  // =========================================
  // SERIAL MONITOR
  // =========================================
  Serial.print("MAG: ");
  Serial.println(magnitude);

  // =========================================
  // KIRIM DATA SENSOR RUTIN (tiap 2 detik)
  // =========================================
  if (millis() - lastSendTime >= sendInterval) {
    sendSensorData(magnitude, ax_g, ay_g, az_g);
    lastSendTime = millis();
  }

  // =========================================
  // IMPACT DETECTION
  // =========================================
  if (magnitude > impactThreshold && !impactDetected) {
    Serial.println("🚨 IMPACT DETECTED!");
    impactDetected = true;
    impactTime = millis();
  }

  // =========================================
  // FALL DETECTION
  // =========================================
  if (impactDetected) {
    unsigned long elapsedTime = millis() - impactTime;

    if (elapsedTime > immobilityTime) {
      if (magnitude < movementThreshold) {
        Serial.println("🆘 FALL DETECTED!");
        alarmActive = true;

        // Kirim ke Laravel
        sendFallAlert(magnitude);

      } else {
        Serial.println("❌ FALSE ALARM");
      }
      impactDetected = false;
    }
  }

  // =========================================
  // BUTTON SOS (TOGGLE)
  // =========================================
  int currentButtonState = digitalRead(SOS_BUTTON);

  if (lastButtonState == HIGH && currentButtonState == LOW) {
    alarmActive = !alarmActive;

    if (alarmActive) {
      Serial.println("🔴 EMERGENCY BUTTON PRESSED");
    } else {
      Serial.println("🟢 ALERT CANCELLED");
    }

    // Kirim ke Laravel
    sendSOSAlert(alarmActive);

    delay(200);
  }

  lastButtonState = currentButtonState;

  // =========================================
  // BUZZER CONTROL
  // =========================================
  digitalWrite(BUZZER_PIN, alarmActive ? HIGH : LOW);

  delay(200);
}
