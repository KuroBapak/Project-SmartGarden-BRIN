#include <Adafruit_ADS1X15.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ST7735.h>
#include <ArduinoJson.h>
#include <DHT.h>
#include <DallasTemperature.h>
#include <OneWire.h>
#include <Preferences.h>
#include <SPI.h>
#include <WebSocketsClient.h>
#include <WiFi.h>
#include <WiFiManager.h>
#include <Wire.h>
#include <esp_task_wdt.h>

// ================= KONFIGURASI JARINGAN (DINAMIS) =================
char mqtt_host[40];
char mqtt_port[6];
char mqtt_user[32];
char mqtt_pass[32];
char mqtt_topic_pub[64];
char mqtt_topic_sub[64];

#define WDT_TIMEOUT 15

// ================= PIN & SENSOR DEFINITIONS =================
#define TFT_CS 15
#define TFT_DC 16
#define TFT_RST 17

#define SDA_PIN 21
#define SCL_PIN 22
#define ADS_ADDR 0x48

#define ONE_WIRE_BUS 4
#define DHTPIN 5
#define DHTTYPE DHT11
#define LDR_PIN 34

#define PIN_PUMP_PH 25
#define PIN_PUMP_TDS 26
#define PIN_PUMP_WATER 27
#define PIN_FAN 14

// ================= OBJECTS & MUTEX =================
WebSocketsClient webSocket;
Adafruit_ST7735 tft = Adafruit_ST7735(TFT_CS, TFT_DC, TFT_RST);
Adafruit_ADS1115 ads;
OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature ds18b20(&oneWire);
DHT dht(DHTPIN, DHTTYPE);
Preferences preferences;

SemaphoreHandle_t sysMutex;

// ================= VARS GLOBAL =================
bool isMqttConnected = false;
unsigned long lastPing = 0;
unsigned long lastWifiCheck = 0;
unsigned long lastPublish = 0;
unsigned long lastSensorRead = 0;
unsigned long lastSlowSensor = 0;

float current_ph = 0.0;
float current_tds = 0.0;
float current_turb = 0.0;
float current_water_temp = 25.0;
float current_air_temp = 25.0;

float pv_voltage = 0.0, pv_power_w = 0.0, pv_current = 0.0;
float battery_voltage = 12.0, battery_percentage = 50.0;
float current_load_w = 15.0, net_power_w = 0.0;
float current_battery_ah = 10.0;
const float battery_capacity_ah = 20.0;
int day_count = -1;
float max_power_today = 50.0;
int weather_type = 0;

String current_preset = "Default";
unsigned long telemetry_interval = 5000;

float cal_ph_p1_ph = 6.86, cal_ph_p1_mv = 1621.0;
float cal_ph_p2_ph = 4.01, cal_ph_p2_mv = 2117.0;
float cal_tds_k = 1.1013;
float cal_turb_zero_v = 2.1;

float raw_ph_mv = 0.0;
float raw_tds_v = 0.0;
float raw_turb_v = 0.0;

#define MAX_RULES 8
struct Rule {
  char sensor[12];
  char condition;
  float value;
  uint8_t pump;
  uint16_t pulse;
  uint16_t stabilize;
  uint8_t max_pulses;
  uint16_t cooldown;
};
Rule rules[MAX_RULES];
int ruleCount = 0;

struct PumpState {
  bool inCorrection;
  bool pumpOn;
  unsigned long pulseStart;
  unsigned long stabilizeStart;
  uint8_t pulsesDone;
  unsigned long cooldownEnd;
  int activeRule;
};
PumpState pumpStates[4] = {};
unsigned long manualEnd[4] = {0, 0, 0, 0};

const int PUMP_PINS[4] = {PIN_PUMP_PH, PIN_PUMP_TDS, PIN_PUMP_WATER, PIN_FAN};

// ================= HELPER UI =================
void drawActuatorBox(int x, int y, const char *label, bool isActive,
                     bool blinkToggle) {
  uint16_t COLOR_DARKGREY = 0x39E7;
  if (isActive) {
    if (blinkToggle) {
      tft.fillRect(x, y, 36, 20, ST77XX_RED);
      tft.setTextColor(ST77XX_WHITE, ST77XX_RED);
    } else {
      tft.fillRect(x, y, 36, 20, ST77XX_BLACK);
      tft.drawRect(x, y, 36, 20, ST77XX_RED);
      tft.setTextColor(ST77XX_RED, ST77XX_BLACK);
    }
  } else {
    tft.fillRect(x, y, 36, 20, ST77XX_BLACK);
    tft.drawRect(x, y, 36, 20, COLOR_DARKGREY);
    tft.setTextColor(COLOR_DARKGREY, ST77XX_BLACK);
  }
  tft.setTextSize(1);
  int offset = (36 - (strlen(label) * 6)) / 2;
  tft.setCursor(x + offset, y + 6);
  tft.print(label);
}

void refreshTFT() {
  tft.setTextSize(1);
  tft.setCursor(5, 5);
  tft.setTextColor(ST77XX_CYAN, ST77XX_BLACK);
  tft.print("AMCS BRIN");

  tft.setCursor(105, 5);
  tft.setTextColor(isMqttConnected ? ST77XX_GREEN : ST77XX_RED, ST77XX_BLACK);
  tft.print(isMqttConnected ? "WSS:OK " : "WSS:OFF");

  tft.setCursor(5, 20);
  tft.setTextColor(ST77XX_YELLOW, ST77XX_BLACK);
  tft.print("Preset: ");
  String p = current_preset;
  while (p.length() < 12)
    p += " ";
  tft.print(p);

  char buf[10];
  tft.setTextColor(ST77XX_WHITE, ST77XX_BLACK);
  tft.setCursor(5, 35);
  tft.print("pH   : ");
  dtostrf(current_ph, 5, 2, buf);
  tft.print(buf);
  tft.setCursor(5, 50);
  tft.print("TDS  : ");
  dtostrf(current_tds, 5, 0, buf);
  tft.print(buf);
  tft.print(" ppm  ");
  tft.setCursor(5, 65);
  tft.print("Suhu : ");
  dtostrf(current_water_temp, 5, 1, buf);
  tft.print(buf);
  tft.print(" C    ");
  tft.setCursor(5, 80);
  tft.print("Turb : ");
  dtostrf(current_turb, 5, 1, buf);
  tft.print(buf);
  tft.print(" NTU  ");

  bool blink = (millis() / 500) % 2 == 0;
  drawActuatorBox(5, 100, "R1", !digitalRead(PIN_PUMP_PH), blink);
  drawActuatorBox(43, 100, "R2", !digitalRead(PIN_PUMP_TDS), blink);
  drawActuatorBox(81, 100, "R3", !digitalRead(PIN_PUMP_WATER), blink);
  drawActuatorBox(119, 100, "R4", !digitalRead(PIN_FAN), blink);
}

float readVoltage(int channel) {
  int16_t adc = ads.readADC_SingleEnded(channel);
  return adc * 0.1875 / 1000.0;
}

// ================= SIMULASI SOLAR PANEL =================
void runSolarSimulation() {
  unsigned long current_time = millis();
  const unsigned long CYCLE_DURATION_MS = 300000;
  float cycle_pos =
      (float)(current_time % CYCLE_DURATION_MS) / CYCLE_DURATION_MS;
  int current_day = current_time / CYCLE_DURATION_MS;

  if (current_day != day_count) {
    day_count = current_day;
    weather_type = random(0, 3);
    if (weather_type == 0)
      max_power_today = random(450, 600) / 10.0;
    else if (weather_type == 1)
      max_power_today = random(200, 350) / 10.0;
    else
      max_power_today = random(50, 150) / 10.0;
  }

  bool is_day = cycle_pos < 0.5;
  float t_pv_voltage = 0.0, t_pv_power_w = 0.0, t_pv_current = 0.0;

  if (is_day) {
    float x = cycle_pos * 2.0;
    t_pv_voltage = 18.0;
    t_pv_power_w = sin(x * PI) * max_power_today;
    float noise = random(-20, 20) / 10.0;
    if (weather_type == 1)
      noise *= 2.5;
    if (weather_type < 2 && random(0, 100) < 15)
      t_pv_power_w *= random(60, 90) / 100.0;
    t_pv_power_w += noise;
    if (t_pv_power_w < 0)
      t_pv_power_w = 0;
    t_pv_current = t_pv_power_w / t_pv_voltage;
  }

  float t_current_load_w = 15.0 + (random(-30, 30) / 10.0);
  float t_net_power_w = t_pv_power_w - t_current_load_w;
  float net_current_a = t_net_power_w / 12.0;

  current_battery_ah += (net_current_a / 3600.0) * 15.0;
  if (current_battery_ah > battery_capacity_ah)
    current_battery_ah = battery_capacity_ah;
  if (current_battery_ah < 0)
    current_battery_ah = 0;

  float t_battery_percentage =
      (current_battery_ah / battery_capacity_ah) * 100.0;
  float t_battery_voltage = 11.5 + ((t_battery_percentage / 100.0) * 1.3);
  if (is_day && t_pv_power_w > 5.0)
    t_battery_voltage += 1.0;

  if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
    pv_voltage = t_pv_voltage;
    pv_power_w = t_pv_power_w;
    pv_current = t_pv_current;
    current_load_w = t_current_load_w;
    net_power_w = t_net_power_w;
    battery_percentage = t_battery_percentage;
    battery_voltage = t_battery_voltage;
    xSemaphoreGive(sysMutex);
  }
}

// ================= MQTT BUILDER =================
void sendMqttConnect() {
  String clientId = "esp32_1";
  uint8_t varHeader[] = {0x00, 0x04, 'M',  'Q',  'T',
                         'T',  0x04, 0xC2, 0x00, 0x3C};
  uint16_t clientLen = clientId.length();

  uint16_t userLen = strlen(mqtt_user);
  uint16_t passLen = strlen(mqtt_pass);

  size_t remainingLen =
      sizeof(varHeader) + (2 + clientLen) + (2 + userLen) + (2 + passLen);
  uint8_t packet[256];
  int idx = 0;
  packet[idx++] = 0x10;
  size_t len = remainingLen;
  do {
    uint8_t d = len % 128;
    len /= 128;
    if (len > 0)
      d |= 0x80;
    packet[idx++] = d;
  } while (len > 0);
  for (int i = 0; i < sizeof(varHeader); i++)
    packet[idx++] = varHeader[i];

  packet[idx++] = (clientLen >> 8);
  packet[idx++] = (clientLen & 0xFF);
  for (int i = 0; i < clientLen; i++)
    packet[idx++] = clientId[i];

  packet[idx++] = (userLen >> 8);
  packet[idx++] = (userLen & 0xFF);
  for (int i = 0; i < userLen; i++)
    packet[idx++] = mqtt_user[i];

  packet[idx++] = (passLen >> 8);
  packet[idx++] = (passLen & 0xFF);
  for (int i = 0; i < passLen; i++)
    packet[idx++] = mqtt_pass[i];

  webSocket.sendBIN(packet, idx);
}

void sendMqttSubscribe(const char *topic) {
  String t = topic;
  size_t rLen = 5 + t.length();
  uint8_t packet[128];
  int idx = 0;
  packet[idx++] = 0x82;
  packet[idx++] = rLen;
  packet[idx++] = 0x00;
  packet[idx++] = 0x01;
  packet[idx++] = (t.length() >> 8);
  packet[idx++] = (t.length() & 0xFF);
  for (int i = 0; i < t.length(); i++)
    packet[idx++] = t[i];
  packet[idx++] = 0x00;
  webSocket.sendBIN(packet, idx);
}

void sendMqttPublish(const char *topic, const char *payload) {
  String t = topic;
  String p = payload;
  size_t rLen = 2 + t.length() + p.length();
  uint8_t packet[1024];
  int idx = 0;
  packet[idx++] = 0x30;
  size_t len = rLen;
  do {
    uint8_t d = len % 128;
    len /= 128;
    if (len > 0)
      d |= 0x80;
    packet[idx++] = d;
  } while (len > 0);
  packet[idx++] = (t.length() >> 8);
  packet[idx++] = (t.length() & 0xFF);
  for (int i = 0; i < t.length(); i++)
    packet[idx++] = t[i];
  for (int i = 0; i < p.length(); i++)
    packet[idx++] = p[i];
  webSocket.sendBIN(packet, idx);
}

// ================= WSS HANDLER =================
void webSocketEvent(WStype_t type, uint8_t *payload, size_t length) {
  switch (type) {
  case WStype_DISCONNECTED:
    isMqttConnected = false;
    break;
  case WStype_CONNECTED:
    sendMqttConnect();
    break;
  case WStype_BIN:
    if (length > 0) {
      if ((payload[0] & 0xF0) == 0x20) {
        isMqttConnected = true;
        sendMqttSubscribe(mqtt_topic_sub);
      } else if ((payload[0] & 0xF0) == 0x30) {
        int h = 2;
        if ((payload[1] & 0x80) != 0)
          h = 3;
        int tLen =
            ((unsigned char)payload[h] << 8) | (unsigned char)payload[h + 1];
        String msg = "";
        for (int i = h + 2 + tLen; i < length; i++)
          msg += (char)payload[i];

        StaticJsonDocument<1536> doc;
        DeserializationError err = deserializeJson(doc, msg);
        if (!err) {
          String action = doc["action"];

          if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
            if (action == "set_config") {
              if (doc.containsKey("preset"))
                current_preset = doc["preset"].as<String>();
              if (doc.containsKey("interval"))
                telemetry_interval = doc["interval"];

              if (doc.containsKey("cal")) {
                JsonObject cal = doc["cal"];
                if (cal.containsKey("ph")) {
                  cal_ph_p1_ph = cal["ph"]["p1_ph"] | 6.86;
                  cal_ph_p1_mv = cal["ph"]["p1_mv"] | 1621.0;
                  cal_ph_p2_ph = cal["ph"]["p2_ph"] | 4.01;
                  cal_ph_p2_mv = cal["ph"]["p2_mv"] | 2117.0;
                }
                if (cal.containsKey("tds"))
                  cal_tds_k = cal["tds"]["k"] | 1.1013;
                if (cal.containsKey("turb"))
                  cal_turb_zero_v = cal["turb"]["zero_v"] | 2.1;
              }

              ruleCount = 0;
              if (doc.containsKey("rules")) {
                JsonArray ra = doc["rules"];
                for (JsonObject ro : ra) {
                  if (ruleCount >= MAX_RULES)
                    break;
                  strlcpy(rules[ruleCount].sensor, ro["s"] | "ph",
                          sizeof(rules[ruleCount].sensor));
                  const char *cond = ro["c"] | "<";
                  rules[ruleCount].condition = cond[0];
                  rules[ruleCount].value = ro["v"] | 0.0;
                  rules[ruleCount].pump = ro["p"] | 1;
                  rules[ruleCount].pulse = ro["pulse"] | 3;
                  rules[ruleCount].stabilize = ro["stab"] | 5;
                  rules[ruleCount].max_pulses = ro["max"] | 10;
                  rules[ruleCount].cooldown = ro["cd"] | 300;
                  ruleCount++;
                }
              }

              preferences.putString("preset", current_preset);
              preferences.putUInt("interval", telemetry_interval);
              preferences.putFloat("cal_p1ph", cal_ph_p1_ph);
              preferences.putFloat("cal_p1mv", cal_ph_p1_mv);
              preferences.putFloat("cal_p2ph", cal_ph_p2_ph);
              preferences.putFloat("cal_p2mv", cal_ph_p2_mv);
              preferences.putFloat("cal_tdsk", cal_tds_k);
              preferences.putFloat("cal_turbz", cal_turb_zero_v);

              String rulesJson;
              serializeJson(doc["rules"], rulesJson);
              preferences.putString("rules", rulesJson);

              for (int i = 0; i < 4; i++)
                pumpStates[i] = {};

            } else if (action == "manual_pump") {
              String target = doc["target"];
              unsigned long dur = doc["duration"];
              unsigned long now = millis();
              for (int i = 0; i < 4; i++) {
                if (target == String("pump_") + String(i + 1)) {
                  manualEnd[i] = now + dur;
                  break;
                }
              }
            }
            xSemaphoreGive(sysMutex);
          }
        }
      }
    }
    break;
  }
}

// ================= TASK 1: JARINGAN & WEB CONFIG (CORE 0) =================
void TaskNetwork(void *pvParameters) {
  WiFiManager wm;
  wm.setConfigPortalTimeout(300);

  WiFiManagerParameter custom_mqtt_host("host", "MQTT Host (WSS)", mqtt_host,
                                        40);
  WiFiManagerParameter custom_mqtt_port("port", "Port (443)", mqtt_port, 6);
  WiFiManagerParameter custom_mqtt_user("user", "MQTT User", mqtt_user, 32);
  WiFiManagerParameter custom_mqtt_pass("pass", "MQTT Password", mqtt_pass, 32);
  WiFiManagerParameter custom_mqtt_pub("pub", "Topic Publish", mqtt_topic_pub,
                                       64);
  WiFiManagerParameter custom_mqtt_sub("sub", "Topic Subscribe", mqtt_topic_sub,
                                       64);

  wm.addParameter(&custom_mqtt_host);
  wm.addParameter(&custom_mqtt_port);
  wm.addParameter(&custom_mqtt_user);
  wm.addParameter(&custom_mqtt_pass);
  wm.addParameter(&custom_mqtt_pub);
  wm.addParameter(&custom_mqtt_sub);

  if (!wm.autoConnect("AMCS-Setup", "password123")) {
    Serial.println("Gagal koneksi WiFi, restart ESP32...");
    delay(3000);
    ESP.restart();
  }

  Serial.println("WiFi Terhubung!");

  // Mengaktifkan Auto-Reconnect bawaan hardware yang efisien
  WiFi.setAutoReconnect(true);

  if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
    preferences.putString("m_host", custom_mqtt_host.getValue());
    preferences.putString("m_port", custom_mqtt_port.getValue());
    preferences.putString("m_user", custom_mqtt_user.getValue());
    preferences.putString("m_pass", custom_mqtt_pass.getValue());
    preferences.putString("m_pub", custom_mqtt_pub.getValue());
    preferences.putString("m_sub", custom_mqtt_sub.getValue());

    strlcpy(mqtt_host, custom_mqtt_host.getValue(), sizeof(mqtt_host));
    strlcpy(mqtt_port, custom_mqtt_port.getValue(), sizeof(mqtt_port));
    strlcpy(mqtt_user, custom_mqtt_user.getValue(), sizeof(mqtt_user));
    strlcpy(mqtt_pass, custom_mqtt_pass.getValue(), sizeof(mqtt_pass));
    strlcpy(mqtt_topic_pub, custom_mqtt_pub.getValue(), sizeof(mqtt_topic_pub));
    strlcpy(mqtt_topic_sub, custom_mqtt_sub.getValue(), sizeof(mqtt_topic_sub));
    xSemaphoreGive(sysMutex);
  }

  static bool wssStarted = false;

  for (;;) {
    unsigned long now = millis();

    // Pemantauan WiFi pasif (tanpa paksaan reconnect manual)
    if (now - lastWifiCheck > 5000) {
      lastWifiCheck = now;
      if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi Terputus... Menunggu Auto-Reconnect Internal ESP");
        isMqttConnected = false; // Mengamankan state WSS/MQTT
      }
    }

    // Eksekusi WSS internal library (Wajib dipanggil untuk pembersihan memori
    // TCP)
    webSocket.loop();

    // Pastikan operasi jaringan hanya berjalan saat terhubung
    if (WiFi.status() == WL_CONNECTED) {

      if (!wssStarted) {
        uint16_t portNum = atoi(mqtt_port);
        webSocket.beginSSL(mqtt_host, portNum, "/mqtt", "", "mqtt");
        String origin = String("Origin: https://") + String(mqtt_host);
        webSocket.setExtraHeaders(origin.c_str());
        webSocket.onEvent(webSocketEvent);
        wssStarted = true;
      }

      // Ping
      if (isMqttConnected && (now - lastPing > 10000)) {
        lastPing = now;
        uint8_t p[] = {0xC0, 0x00};
        webSocket.sendBIN(p, 2);
      }

      // Publikasi Telemetri
      if (isMqttConnected && (now - lastPublish >= telemetry_interval)) {
        lastPublish = now;
        StaticJsonDocument<768> doc;

        // Ambil data dari shared memory dengan cepat
        if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
          doc["preset"] = current_preset;
          doc["ph"] = current_ph;
          doc["tds"] = current_tds;
          doc["turbidity"] = current_turb;
          doc["water_temp"] = current_water_temp;
          doc["air_temp"] = current_air_temp;

          doc["pv_voltage"] = pv_voltage;
          doc["pv_current"] = pv_current;
          doc["pv_power"] = pv_power_w;
          doc["battery_voltage"] = battery_voltage;
          doc["battery_percentage"] = battery_percentage;
          doc["load_power"] = current_load_w;
          doc["net_power"] = net_power_w;

          doc["raw_ph_mv"] = raw_ph_mv;
          doc["raw_tds_v"] = raw_tds_v;
          doc["raw_turb_v"] = raw_turb_v;

          bool anyCorrection = false;
          for (int i = 0; i < 4; i++)
            if (pumpStates[i].inCorrection)
              anyCorrection = true;
          doc["mode"] = anyCorrection ? "correction" : "normal";
          xSemaphoreGive(sysMutex);
        }

        // Pengambilan data sensor langsung (Tidak butuh perlindungan Mutex)
        doc["humidity"] = dht.readHumidity();
        doc["light"] = (analogRead(LDR_PIN) / 4095.0) * 100.0;
        doc["rssi"] = WiFi.RSSI();
        doc["pump_1"] = (digitalRead(PUMP_PINS[0]) == LOW) ? 1 : 0;
        doc["pump_2"] = (digitalRead(PUMP_PINS[1]) == LOW) ? 1 : 0;
        doc["pump_3"] = (digitalRead(PUMP_PINS[2]) == LOW) ? 1 : 0;
        doc["pump_4"] = (digitalRead(PUMP_PINS[3]) == LOW) ? 1 : 0;

        char jsonBuffer[768];
        serializeJson(doc, jsonBuffer);
        sendMqttPublish(mqtt_topic_pub, jsonBuffer);
      }
    }

    vTaskDelay(10 / portTICK_PERIOD_MS);
  }
}

// ================= TASK 2: SENSOR & KONTROL UI (CORE 1) =================
void TaskSensorControl(void *pvParameters) {
  for (;;) {
    unsigned long now = millis();

    if (now - lastSlowSensor >= 5000) {
      lastSlowSensor = now;
      float tempRead = dht.readTemperature();
      ds18b20.requestTemperatures();
      float waterTempRead = ds18b20.getTempCByIndex(0);

      if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
        if (!isnan(tempRead))
          current_air_temp = tempRead;
        if (waterTempRead != DEVICE_DISCONNECTED_C && waterTempRead > -10)
          current_water_temp = waterTempRead;
        xSemaphoreGive(sysMutex);
      }
    }

    if (now - lastSensorRead >= 500) {
      lastSensorRead = now;

      // Optimasi Pembacaan Float (Di luar pelindung Mutex)
      float phVoltage_V = readVoltage(0);
      float t_raw_ph_mv = phVoltage_V * 1000.0;
      float t_raw_tds_v = readVoltage(1);
      float t_raw_turb_v = readVoltage(2);

      if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
        raw_ph_mv = t_raw_ph_mv;
        raw_tds_v = t_raw_tds_v;
        raw_turb_v = t_raw_turb_v;

        float base_slope =
            (cal_ph_p1_ph - cal_ph_p2_ph) / (cal_ph_p1_mv - cal_ph_p2_mv);
        float temp_ratio = 298.15 / (current_water_temp + 273.15);
        float compensated_slope = base_slope * temp_ratio;
        float raw_ph =
            cal_ph_p1_ph + compensated_slope * (raw_ph_mv - cal_ph_p1_mv);
        current_ph =
            (current_ph == 0.0) ? raw_ph : (current_ph * 0.8) + (raw_ph * 0.2);

        float compensationCoefficient =
            1.0 + 0.02 * (current_water_temp - 25.0);
        if (compensationCoefficient <= 0.0)
          compensationCoefficient = 1.0;
        float compensationVoltage = raw_tds_v / compensationCoefficient;
        float raw_tds_default = (133.42 * pow(compensationVoltage, 3) -
                                 255.86 * pow(compensationVoltage, 2) +
                                 857.39 * compensationVoltage) *
                                0.417;
        float raw_tds = raw_tds_default * cal_tds_k;
        current_tds = (raw_tds < 0) ? 0 : (current_tds * 0.8) + (raw_tds * 0.2);

        float turbVoltage = raw_turb_v + (cal_turb_zero_v - 2.1 + 0.928);
        float raw_turb =
            -1120.4 * pow(turbVoltage, 2) + 5742.3 * turbVoltage - 4352.9;
        current_turb =
            (raw_turb < 0) ? 0 : (current_turb * 0.8) + (raw_turb * 0.2);
        xSemaphoreGive(sysMutex);
      }

      runSolarSimulation();

      // Logika Aktuator
      if (xSemaphoreTake(sysMutex, portMAX_DELAY)) {
        float sensorVals[5] = {current_ph, current_tds, current_turb,
                               current_water_temp, current_air_temp};
        const char *sensorKeys[5] = {"ph", "tds", "turbidity", "water_temp",
                                     "air_temp"};

        for (int r = 0; r < ruleCount; r++) {
          int pIdx = rules[r].pump - 1;
          if (pIdx < 0 || pIdx > 3)
            continue;
          PumpState &ps = pumpStates[pIdx];

          float sVal = 0;
          for (int s = 0; s < 5; s++) {
            if (strcmp(rules[r].sensor, sensorKeys[s]) == 0) {
              sVal = sensorVals[s];
              break;
            }
          }

          bool violated = (rules[r].condition == '<') ? (sVal < rules[r].value)
                                                      : (sVal > rules[r].value);

          if (violated && !ps.inCorrection && now > ps.cooldownEnd) {
            ps.inCorrection = true;
            ps.pumpOn = true;
            ps.pulseStart = now;
            ps.pulsesDone = 0;
            ps.activeRule = r;
          }

          if (ps.inCorrection && ps.activeRule == r) {
            if (ps.pumpOn) {
              if (now - ps.pulseStart >= (unsigned long)rules[r].pulse * 1000) {
                ps.pumpOn = false;
                ps.stabilizeStart = now;
                ps.pulsesDone++;
              }
            } else {
              if (now - ps.stabilizeStart >=
                  (unsigned long)rules[r].stabilize * 1000) {
                if (!violated) {
                  ps.inCorrection = false;
                } else if (ps.pulsesDone >= rules[r].max_pulses) {
                  ps.inCorrection = false;
                  ps.cooldownEnd =
                      now + (unsigned long)rules[r].cooldown * 1000;
                } else {
                  ps.pumpOn = true;
                  ps.pulseStart = now;
                }
              }
            }
          }
        }

        for (int i = 0; i < 4; i++) {
          bool shouldBeOn =
              (now < manualEnd[i]) ||
              (pumpStates[i].inCorrection && pumpStates[i].pumpOn);
          digitalWrite(PUMP_PINS[i], shouldBeOn ? LOW : HIGH);
        }
        xSemaphoreGive(sysMutex);
      }

      refreshTFT();
    }
    vTaskDelay(5 / portTICK_PERIOD_MS);
  }
}

// ================= SETUP =================
void setup() {
  Serial.begin(115200);

  sysMutex = xSemaphoreCreateMutex();

  tft.initR(INITR_BLACKTAB);
  tft.setRotation(1);
  tft.fillScreen(ST77XX_BLACK);
  tft.setTextColor(ST77XX_CYAN);
  tft.setTextSize(2);
  tft.setCursor(15, 50);
  tft.print("BOOTING...");

  Wire.begin(SDA_PIN, SCL_PIN);
  Wire.setTimeOut(1000);

  pinMode(PIN_PUMP_PH, OUTPUT);
  pinMode(PIN_PUMP_TDS, OUTPUT);
  pinMode(PIN_PUMP_WATER, OUTPUT);
  pinMode(PIN_FAN, OUTPUT);

  digitalWrite(PIN_PUMP_PH, HIGH);
  digitalWrite(PIN_PUMP_TDS, HIGH);
  digitalWrite(PIN_PUMP_WATER, HIGH);
  digitalWrite(PIN_FAN, HIGH);

  preferences.begin("smartwater", false);

  String def_host = preferences.getString("m_host", "mqtt.kurobapak.site");
  String def_port = preferences.getString("m_port", "443");
  String def_user = preferences.getString("m_user", "ESP32");
  String def_pass = preferences.getString("m_pass", "sudomoreno");
  String def_pub =
      preferences.getString("m_pub", "brin/water/esp32_1/up/telemetry");
  String def_sub =
      preferences.getString("m_sub", "brin/water/esp32_1/down/cmd");

  strlcpy(mqtt_host, def_host.c_str(), sizeof(mqtt_host));
  strlcpy(mqtt_port, def_port.c_str(), sizeof(mqtt_port));
  strlcpy(mqtt_user, def_user.c_str(), sizeof(mqtt_user));
  strlcpy(mqtt_pass, def_pass.c_str(), sizeof(mqtt_pass));
  strlcpy(mqtt_topic_pub, def_pub.c_str(), sizeof(mqtt_topic_pub));
  strlcpy(mqtt_topic_sub, def_sub.c_str(), sizeof(mqtt_topic_sub));

  current_preset = preferences.getString("preset", "Default");
  telemetry_interval = preferences.getUInt("interval", 5000);
  cal_ph_p1_ph = preferences.getFloat("cal_p1ph", 6.86);
  cal_ph_p1_mv = preferences.getFloat("cal_p1mv", 1621.0);
  cal_ph_p2_ph = preferences.getFloat("cal_p2ph", 4.01);
  cal_ph_p2_mv = preferences.getFloat("cal_p2mv", 2117.0);
  cal_tds_k = preferences.getFloat("cal_tdsk", 1.1013);
  cal_turb_zero_v = preferences.getFloat("cal_turbz", 2.1);

  String savedRules = preferences.getString("rules", "[]");
  StaticJsonDocument<1024> rdoc;
  if (!deserializeJson(rdoc, savedRules)) {
    JsonArray ra = rdoc.as<JsonArray>();
    ruleCount = 0;
    for (JsonObject ro : ra) {
      if (ruleCount >= MAX_RULES)
        break;
      strlcpy(rules[ruleCount].sensor, ro["s"] | "ph",
              sizeof(rules[ruleCount].sensor));
      const char *cond = ro["c"] | "<";
      rules[ruleCount].condition = cond[0];
      rules[ruleCount].value = ro["v"] | 0.0;
      rules[ruleCount].pump = ro["p"] | 1;
      rules[ruleCount].pulse = ro["pulse"] | 3;
      rules[ruleCount].stabilize = ro["stab"] | 5;
      rules[ruleCount].max_pulses = ro["max"] | 10;
      rules[ruleCount].cooldown = ro["cd"] | 300;
      ruleCount++;
    }
  }

  esp_task_wdt_deinit();
  esp_task_wdt_config_t wdt_config = {.timeout_ms = WDT_TIMEOUT * 1000,
                                      .idle_core_mask =
                                          (1 << portNUM_PROCESSORS) - 1,
                                      .trigger_panic = true};
  esp_task_wdt_init(&wdt_config);
  esp_task_wdt_add(NULL);

  if (!ads.begin(ADS_ADDR))
    Serial.println("ADS FAIL");
  ads.setGain(GAIN_TWOTHIRDS);

  dht.begin();
  ds18b20.begin();

  tft.fillScreen(ST77XX_BLACK);
  tft.drawLine(0, 15, 160, 15, ST77XX_WHITE);
  refreshTFT();

  xTaskCreatePinnedToCore(TaskNetwork, "NetworkTask", 8192, NULL, 1, NULL, 0);
  xTaskCreatePinnedToCore(TaskSensorControl, "ControlUI", 8192, NULL, 1, NULL,
                          1);
}

// ================= MAIN LOOP =================
void loop() {
  esp_task_wdt_reset();
  vTaskDelay(1000 / portTICK_PERIOD_MS);
}