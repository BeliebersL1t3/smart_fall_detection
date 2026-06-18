#include <Wire.h>
#include <math.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// =====================================================
// WIFI
// =====================================================
const char* WIFI_SSID     = "KONTRAKAN BARU";
const char* WIFI_PASSWORD = "kuncipintu";

// =====================================================
// LARAVEL API (Railway Production)
// =====================================================
const char* API_BASE_URL  = "https://smartfalldetection-production.up.railway.app";
const char* DEVICE_TOKEN  = "GANTI_DENGAN_TOKEN_DI_SETTINGS"; // Lihat: Settings → ESP32 API Credentials → Device Token

// =====================================================
// ENDPOINT
// =====================================================
const char* ENDPOINT_SENSOR = "/api/sensor-data";
const char* ENDPOINT_FALL   = "/api/fall-detected";
const char* ENDPOINT_SOS    = "/api/sos";

// =====================================================
// PIN CONFIG
// =====================================================
#define SDA_PIN        22
#define SCL_PIN        23

#define BUZZER_PIN     33
#define SOS_BUTTON     25

#define BATTERY_PIN    34

// =====================================================
// FALL DETECTION CONFIG
// =====================================================
float impactThreshold   = 2.8;
float movementThreshold = 1.2;

unsigned long immobilityTime = 3000;

bool impactDetected = false;

bool fallAlarm = false;
bool sosAlarm  = false;

unsigned long impactTime = 0;

// =====================================================
// BUTTON
// =====================================================
int lastButtonState = HIGH;

// =====================================================
// TIMER
// =====================================================
unsigned long lastSendTime = 0;
const unsigned long sendInterval = 2000;

// =====================================================
// GET FINAL ALARM STATE
// =====================================================
bool getAlarmState() {
  return fallAlarm || sosAlarm;
}

// =====================================================
// WIFI CONNECT
// =====================================================
void connectWiFi() {
  Serial.print("Connecting WiFi");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 20) {
    delay(500);
    Serial.print(".");
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WiFi Connected");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n❌ WiFi Failed");
  }
}

// =====================================================
// READ BATTERY (REAL-TIME)
// =====================================================
float readBatteryPercentage() {
  // 1. Baca nilai ADC dari pin baterai (ESP32 default 12-bit: 0 - 4095)
  int adcValue = analogRead(BATTERY_PIN);
  
  // 2. Ubah nilai ADC menjadi tegangan fisik yang masuk ke pin ESP32 (0 - 3.3V)
  float pinVoltage = (adcValue / 4095.0) * 3.3;
  
  // 3. Hitung tegangan baterai asli berdasarkan rasio resistor divider.
  // Jika menggunakan R1 = 10k Ohm dan R2 = 10k Ohm, pembagian tegangannya adalah setengahnya.
  // Kalibrasi faktor pengali (2.0) ini sesuai dengan hasil ukur multimeter jika diperlukan.
  float batteryVoltage = pinVoltage * 2.0; 
  
  // 4. Konversi tegangan baterai ke rentang persentase (Asumsi Li-ion / LiPo 1S)
  // Tegangan penuh (100%) = 4.2V, Tegangan kosong (0%) = 3.3V
  float percentage = ((batteryVoltage - 3.3) / (4.2 - 3.3)) * 100.0;
  
  // 5. Batasi nilai persentase agar tetap berada di rentang aman 0% hingga 100%
  if (percentage > 100.0) percentage = 100.0;
  if (percentage < 0.0)   percentage = 0.0;
  
  // Tampilkan data ke Serial Monitor untuk keperluan monitoring/kalibrasi
  Serial.printf("[BATTERY] ADC: %d | Pin V: %.2fV | Bat V: %.2fV | %0.1f%%\n", 
                adcValue, pinVoltage, batteryVoltage, percentage);

  return percentage;
}

// =====================================================
// SEND SENSOR DATA
// =====================================================
void sendSensorData(float mag, float ax, float ay, float az, float battery) {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClientSecure client;
  client.setInsecure(); // Skip SSL cert verification (cukup untuk production internal)

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_SENSOR;

  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<256> doc;
  doc["magnitude"] = mag;
  doc["ax"] = ax;
  doc["ay"] = ay;
  doc["az"] = az;
  doc["battery"] = battery;
  doc["status"] = getAlarmState() ? "alarm" : "normal";

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);

  if (httpCode == 200 || httpCode == 201) {
    // --- BAGIAN BARU: MEMBACA RESPON SERVER ---
    String responseBody = http.getString();
    StaticJsonDocument<512> resDoc;
    DeserializationError error = deserializeJson(resDoc, responseBody);

    if (!error) {
      // Ambil perintah buzzer dari server (command_buzzer yang kita buat di Laravel tadi)
      bool shouldBuzzerBeOn = resDoc["telemetry"]["command_buzzer"]; 

      // Jika di ESP32 alarm masih nyala (lokal), tapi server bilang "false" (dismissed)
      if (getAlarmState() == true && shouldBuzzerBeOn == false) {
        Serial.println("🔕 ALARM DISMISSED FROM DASHBOARD!");
        
        // Matikan semua flag alarm di ESP32
        fallAlarm = false;
        sosAlarm = false;
        impactDetected = false;
        
        // Matikan buzzer secara fisik
        digitalWrite(BUZZER_PIN, LOW);
      }
    }
    // ------------------------------------------
    Serial.println("📡 Data sent & Sync success");
  } else {
    Serial.printf("⚠️ HTTP Error: %d\n", httpCode);
  }

  http.end();
}

// =====================================================
// SEND FALL ALERT
// =====================================================
void sendFallAlert(float mag) {
  if (WiFi.status() != WL_CONNECTED)
    return;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_FALL;

  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);

  StaticJsonDocument<256> doc;
  doc["event"] = "fall_detected";
  doc["magnitude"] = mag;
  doc["message"] = "Pengguna terdeteksi jatuh!";
  doc["active"] = true;

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);
  Serial.print("HTTP FALL: ");
  Serial.println(httpCode);

  if (httpCode == 200 || httpCode == 201) {
    Serial.println("🆘 FALL alert sent!");
  } else {
    Serial.printf("⚠️ Fall Error: %d\n", httpCode);
  }

  http.end();
}

// =====================================================
// SEND SOS ALERT
// =====================================================
void sendSOSAlert(bool isActive) {
  if (WiFi.status() != WL_CONNECTED)
    return;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(API_BASE_URL) + ENDPOINT_SOS;

  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<256> doc;
  doc["event"] = isActive ? "sos_active" : "sos_cancelled";
  doc["message"] = isActive ? "Tombol SOS ditekan!" : "Alarm SOS dibatalkan";
  doc["active"] = isActive;

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);
  Serial.print("HTTP SOS: ");
  Serial.println(httpCode);

  if (httpCode == 200 || httpCode == 201) {
    Serial.println("📲 SOS alert sent!");
  } else {
    Serial.printf("⚠️ SOS Error: %d\n", httpCode);
  }

  http.end();
}

// =====================================================
// SETUP
// =====================================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  // MPU6050 I2C
  Wire.begin(SDA_PIN, SCL_PIN);

  // WAKE UP MPU6050
  Wire.beginTransmission(0x68);
  Wire.write(0x6B);
  Wire.write(0);
  Wire.endTransmission(true);

  // BUZZER
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  // BUTTON
  pinMode(SOS_BUTTON, INPUT_PULLUP);

  // ADC CONFIGURATION
  analogReadResolution(12); // Pastikan resolusi ADC ESP32 di-set ke 12-bit (0-4095)

  Serial.println("=================================");
  Serial.println(" SMART FALL DETECTION SYSTEM ");
  Serial.println("=================================");

  connectWiFi();

  Serial.println("✅ SYSTEM READY");
}

// =====================================================
// LOOP
// =====================================================
void loop() {
  // =========================================
  // WIFI RECONNECT
  // =========================================
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  // =========================================
  // READ MPU6050
  // =========================================
  Wire.beginTransmission(0x68);
  Wire.write(0x3B);

  if (Wire.endTransmission(false) != 0) {
    Serial.println("❌ MPU6050 NOT DETECTED");
    delay(1000);
    return;
  }

  Wire.requestFrom(0x68, 6, true);

  int16_t ax_raw = Wire.read() << 8 | Wire.read();
  int16_t ay_raw = Wire.read() << 8 | Wire.read();
  int16_t az_raw = Wire.read() << 8 | Wire.read();

  float ax_g = ax_raw / 16384.0;
  float ay_g = ay_raw / 16384.0;
  float az_g = az_raw / 16384.0;

  // =========================================
  // MAGNITUDE
  // =========================================
  float magnitude = sqrt((ax_g * ax_g) + (ay_g * ay_g) + (az_g * az_g));
  Serial.print("MAG: ");
  Serial.println(magnitude);

  // =========================================
  // BATTERY (REAL-TIME)
  // =========================================
  int batteryPercentage = (int)readBatteryPercentage(); 

  // =========================================
  // SEND SENSOR DATA PERIODICALLY
  // =========================================
  if (millis() - lastSendTime >= sendInterval) {
    sendSensorData(
      magnitude,
      ax_g,
      ay_g,
      az_g,
      batteryPercentage
    );
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
        fallAlarm = true;
        digitalWrite(BUZZER_PIN, HIGH);
        sendFallAlert(magnitude);
      } else {
        Serial.println("❌ FALSE ALARM");
      }
      impactDetected = false;
    }
  }

  // =========================================
  // SOS BUTTON
  // =========================================
  int currentButtonState = digitalRead(SOS_BUTTON);

  if (lastButtonState == HIGH && currentButtonState == LOW) {
    sosAlarm = !sosAlarm;

    if (sosAlarm) {
      Serial.println("🔴 EMERGENCY BUTTON");
    } else {
      Serial.println("🟢 ALERT CANCELLED");
      // RESET ALL
      fallAlarm = false;
      sosAlarm  = false;
    }

    // BUZZER LANGSUNG RESPON
    digitalWrite(BUZZER_PIN, getAlarmState() ? HIGH : LOW);

    // KIRIM SOS
    sendSOSAlert(sosAlarm);

    // UPDATE STATUS DASHBOARD LANGSUNG
    sendSensorData(
      magnitude,
      ax_g,
      ay_g,
      az_g,
      batteryPercentage
    );
  }

  lastButtonState = currentButtonState;

  // =========================================
  // FINAL BUZZER CONTROL
  // =========================================
  digitalWrite(BUZZER_PIN, getAlarmState() ? HIGH : LOW);
}