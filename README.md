# SmartGarden BRIN — AMCS Dashboard

**Autonomous Monitoring & Control System for Agricultural Greenhouse**

> Sistem monitoring dan kontrol otomatis untuk greenhouse pertanian, dibangun dengan arsitektur distributed (Laravel Dashboard + FastAPI AI Server + ESP32 Hardware).

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.115-009688?logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com)
[![YOLOv8](https://img.shields.io/badge/YOLOv8-Plant_Disease-purple)](https://docs.ultralytics.com)
[![YOLOv8](https://img.shields.io/badge/YOLOv8-Plant_Disease-purple)](https://docs.ultralytics.com)

---

## Table of Contents

- [System Architecture](#system-architecture)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation & Setup](#installation--setup)
- [Installation & Setup](#installation--setup)
- [Running the System](#running-the-system)
- [Sensor Calibration](#sensor-calibration)
- [MQTT Protocol](#mqtt-protocol)
- [API Reference](#api-reference)
- [Project Structure](#project-structure)
- [Hardware (ESP32)](#hardware-esp32)
- [Changelog](#changelog)
- [Team](#team)

---

## System Architecture

```
+------------------------------------------------------------------+
|                         GREENHOUSE                               |
|  +----------+  +----------+  +----------+  +-----------------+  |
|  |  ESP32   |  | Sensors  |  |  Relays  |  | Raspberry Pi    |  |
|  | MQTT/WSS |  | pH/TDS/  |  | R1 R2    |  | Camera Stream   |  |
|  +----+-----+  | Turb/etc |  | R3 R4    |  +-------+---------+  |
|       |        +----------+  +----^-----+          |             |
+-------+----------------------------+----------------+-------------+
        | MQTT over WSS              | MQTT CMD       | HTTP Stream
        v                            |                v
+------------------+         +-------+--------------------------------+
|   EMQX Broker    |         |       AI SERVER  (FastAPI :8001)       |
|  mqtt.broker.com |         |                                        |
+--------+---------+         |  Rule-Based Engine YOLOv8 Detection    |
         |                   |  Battery AI    InfluxDB Query          |
         | WebSocket         |  BMKG Weather  APScheduler             |
         v                   |  - Energy analysis: every 30 min       |
+------------------+         |  - Plant scan: push from Raspi Pi      |
| WEB DASHBOARD    |<------->+----------------------------------------+
| Laravel  :8000   |  REST API (X-API-Key)
|                  |
|  Monitoring      |
|  Charts          |         +------------------+
|  Manual Control  +-------->|    InfluxDB      |
|  AI Results      |         |  (Time-Series DB)|
|  Settings        |         +------------------+
+------------------+
```

### Data Flow

| # | Flow | Description |
|---|------|-------------|
| 1 | ESP32 → EMQX → Dashboard | Real-time sensor telemetry via MQTT WebSocket |
| 2 | Dashboard → ESP32 | Manual pump/relay commands via MQTT |
| 3 | Dashboard → ESP32 | Calibration & config sync via MQTT `set_config` |
| 4 | AI Server → InfluxDB | Fetch solar panel data for energy analysis |
| 5 | AI Server → BMKG API | Fetch 3-day weather forecast |
| 6 | AI Server | Rule-based analysis for energy recommendations |
| 7 | Raspberry Pi → AI Server | Push captured image for disease detection via HTTP |
| 8 | AI Server → Dashboard API | Push computed results to DB (X-API-Key) |
| 9 | Dashboard JS → DB | Polling AI results every 30 seconds |

---

## Features

### Real-Time Monitoring
- **Water sensors**: pH level, TDS (ppm), Turbidity (NTU), Water Temperature
- **Environment sensors**: Air Temperature, Humidity, Light Level (%), WiFi RSSI
- **Raw values**: Raw ADC/voltage shown beneath each calculated value for debugging
- **Historical charts**: Interactive Chart.js graphs with 5 selectable time ranges

### Energy Management
- Solar panel monitoring: PV voltage, current, power via InfluxDB
- Battery tracking: percentage, voltage, endurance estimation
- Smart Battery AI: 3 modes — Normal, Mode Hemat Energi, Emergency

### AI Recommendations
- Automated energy analysis every 30 minutes (embedded in AI Server scheduler)
- Context: real sensor data + solar data + BMKG 24h weather forecast
- Output: concise bullet-point recommendations displayed on dashboard

### Plant Disease Detection (YOLOv8)
- **Push-based Architecture**: Raspberry Pi runs `raspi_sender.py` to capture and upload images directly to the AI Server via `/api/scan/upload`
- Custom-trained model for greenhouse plant diseases
- Full scan history with annotated images on the Dashboard

### Manual Actuator Control
- 4 relays (R1–R4) with configurable duration (seconds or minutes)
- **Safety cap**: max 2 hours per manual override
- **Cancellable**: click a running relay to stop it immediately
- Sends `duration: 0` MQTT command to cut power on ESP32 instantly

### Sensor Calibration (Web-Based, No Firmware Reflash)
- **pH (2-point)**: Buffer 6.86 and 4.01 calibration via dashboard
- **TDS (K-value)**: Slope correction using known standard solution
- **Turbidity (zero-point)**: Zero voltage in clean water
- Calibration pushed live to ESP32 via MQTT, saved to NVS flash
- Also stored in Laravel DB — re-sync anytime via Settings → Save & Sync

### Plant Presets & Automation Rules
- Define sensor threshold rules per plant type
- Pulse & Check algorithm prevents over-dosing
- Parameters: Pulse duration, Stabilize time, Max pulses, Cooldown

---

## Tech Stack

### Dashboard (Laravel)
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.2+ | Runtime |
| Laravel | 12 | Web framework |
| SQLite / MySQL | — | Primary database |
| Alpine.js | 3 | Reactive UI components |
| Chart.js | 4 | Historical sensor charts |
| MQTT.js | — | WebSocket connection to EMQX |
| Vite | 7 | Frontend asset bundler |

### AI Server (FastAPI)
| Technology | Version | Purpose |
|------------|---------|---------|
| Python | 3.11+ | Runtime |
| FastAPI | 0.115 | API framework |
| Uvicorn | — | ASGI server |
| APScheduler | 3.x | Periodic task scheduler (embedded) |
| Ultralytics | 8.x | YOLOv8 plant disease detection |
| Ultralytics | 8.x | YOLOv8 plant disease detection |
| httpx | — | Async HTTP client |
| paho-mqtt | 2.1 | MQTT subscriber for sensor data |
| InfluxDB Client | 1.x | Query solar time-series data |

### Infrastructure
| Component | Technology |
|-----------|-----------|
| MQTT Broker | EMQX (cloud or local) |
| Time-Series DB | InfluxDB 2.x |
| Microcontroller | ESP32 (custom firmware `code_amcs.ino`) |
| Camera | Raspberry Pi + Pi Camera + Flask streamer |

---

## Prerequisites

### Dashboard Server
- PHP >= 8.2 with extensions: `pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `curl`
- Composer 2.x
- Node.js >= 18 + npm

### AI Server
- Python >= 3.11
- Virtual environment (`venv`) inside `AIServer/`
- GPU recommended for YOLO inference (CPU fallback works)

### Infrastructure
- EMQX MQTT Broker accessible via WebSocket
- InfluxDB 2.x with buckets: `sensor_data` and `solar_data`
- Raspberry Pi with camera + Flask HTTP stream (for plant scan)

---

## Installation & Setup

### 1. Clone Repository

```bash
git clone https://github.com/HuangMingZhi0206/Dashboard-AMCS-Replika-BRIN-AI.git
cd Dashboard-AMCS-Replika-BRIN-AI
```

### 2. Setup Dashboard (Laravel)

```bash
# Install PHP dependencies
composer install

# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Setup SQLite database
php artisan migrate

# Seed default calibration data
php artisan db:seed --class=SmartGardenSeeder

# Install frontend dependencies
npm install
```

### 3. Setup AI Server (Python)

```bash
cd AIServer

# Create virtual environment
python -m venv venv

# Activate venv (Windows)
.\venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Configure environment
cp .env.example .env
# Edit .env with your values
```

---

## Running the System

### Quick Start (Recommended) — One Script

Run everything with a single command from the project root:

```powershell
.\start-dev.ps1
```

This script automatically:
1. Builds Vite frontend assets (`npm run build`)
2. Clears and re-optimizes Laravel cache
3. Opens a **new terminal window** for Laravel server (`:8000`)
4. Opens a **new terminal window** for AI Server (`:8001`)

> The AI Scheduler (energy analysis + plant scan) runs **inside** the AI Server process via APScheduler — **no third terminal needed**.

---

### Manual Start (Alternative)

**Terminal 1 — Laravel Dashboard:**
```powershell
npm run build
php artisan optimize:clear
php artisan optimize
php artisan serve --port=8000
```

**Terminal 2 — AI Server:**
```powershell
cd AIServer
.\venv\Scripts\activate
python -m uvicorn main:app --host 0.0.0.0 --port 8001
```

### Access Points

| Service | URL |
|---------|-----|
| Dashboard | http://localhost:8000 |
| AI Server | http://localhost:8001 |
| AI API Docs | http://localhost:8001/docs |

---

## Deployment (Docker)

Sistem ini memiliki dua skema deployment Docker Compose di root folder:

### 1. Server Cloud (Coolify)
Menggunakan **`docker-compose.yml`**. Skema ini diperuntukkan untuk deployment di VPS menggunakan Coolify.
- Menarik image `ghcr.io` dari Github Container Registry.
- Bergabung dengan network `coolify` (Database di-handle terpisah oleh Coolify).
- Menjalankan container `web` (Laravel Dashboard) dan `ai-server`.

### 2. Standalone Server (Pak Yogi)
Menggunakan **`docker-compose.yogi.yml`**. Skema ini diperuntukkan untuk server mandiri.
- Mem-build image Laravel dan AI Server secara lokal.
- Menyertakan container **MySQL 8.0** (`db`).
- Port mapping `7000` (Dashboard) dan `7001` (AI Server).
- Dijalankan dengan perintah: `docker compose -f docker-compose.yogi.yml up -d`

---

## Sensor Calibration

Calibration is done entirely from the web dashboard — **no firmware reflash required**.

### How it works
1. Go to **Settings → Kalibrasi Sensor** tab
2. Adjust calibration values (live raw voltage is shown from ESP32 telemetry)
3. Click **Save & Sync Config** — values are pushed to ESP32 via MQTT
4. ESP32 saves them to NVS flash (survives reboot)
5. Values are also stored in the Laravel DB for re-sync if needed

### Default calibration values (hardcoded in firmware)
| Parameter | Default | Description |
|-----------|---------|-------------|
| `p1_ph` | 6.86 | Buffer 1 pH value |
| `p1_mv` | 1621.0 mV | Buffer 1 raw voltage |
| `p2_ph` | 4.01 | Buffer 2 pH value |
| `p2_mv` | 2117.0 mV | Buffer 2 raw voltage |
| `tds_k` | 1.1013 | TDS K-value correction factor |
| `turb_zero_v` | 2.1 V | Turbidity zero-point voltage (clean water) |

> If the ESP32 NVS flash is erased (e.g. full firmware flash with `--erase-flash`), it falls back to these defaults. Re-sync by opening Settings and clicking **Save & Sync Config**.

---

## MQTT Protocol

### Topics

| Topic | Direction | Description |
|-------|-----------|-------------|
| `brin/water/{device_id}/up/telemetry` | ESP32 → Dashboard | Sensor telemetry (JSON) |
| `brin/water/{device_id}/down/cmd` | Dashboard → ESP32 | Commands & config (JSON) |

### Telemetry Payload (ESP32 → Dashboard)

```json
{
  "preset": "Default",
  "ph": 7.12,
  "tds": 420,
  "turbidity": 2.5,
  "water_temp": 25.0,
  "air_temp": 30.2,
  "humidity": 62,
  "light": 45.3,
  "rssi": -52,
  "raw_ph_mv": 1598.4,
  "raw_tds_v": 1.170,
  "raw_turb_v": 3.274,
  "pump_1": 0,
  "pump_2": 0,
  "pump_3": 0,
  "pump_4": 0,
  "mode": "normal"
}
```

### Command Payloads (Dashboard → ESP32)

**Manual Relay Override:**
```json
{
  "action": "manual_pump",
  "target": "pump_1",
  "duration": 30000
}
```
> `duration` in milliseconds. Send `"duration": 0` to cancel/stop immediately.

**Config & Calibration Sync:**
```json
{
  "action": "set_config",
  "interval": 60000,
  "preset": "Tomat Cherry",
  "cal": {
    "ph":   { "p1_ph": 6.86, "p1_mv": 1621.0, "p2_ph": 4.01, "p2_mv": 2117.0 },
    "tds":  { "k": 1.1013 },
    "turb": { "zero_v": 2.1 }
  },
  "rules": [
    { "s": "ph", "c": "<", "v": 6.5, "p": 1, "pulse": 3, "stab": 5, "max": 10, "cd": 300 }
  ]
}
```

---

## API Reference

### AI Server → Dashboard (Authenticated with `X-API-Key` header)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/ai/energy-analysis` | Push energy analysis result |
| `POST` | `/api/ai/plant-scan` | Push plant scan result + images |

### Dashboard → Frontend (Session auth — logged-in users only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/ai/energy-analysis/latest` | Latest AI energy analysis |
| `GET` | `/api/ai/plant-scan/latest` | Latest plant scan result |
| `GET` | `/api/ai/plant-scan/history` | Scan history (last 50) |
| `GET` | `/api/ai/plant-scan/{id}` | Specific scan by ID |
| `POST` | `/api/ai/plant-scan/trigger` | Trigger manual capture |
| `GET` | `/api/solar` | Real-time solar panel data |
| `GET` | `/api/bmkg/forecast` | BMKG weather forecast |

### AI Server Internal (Swagger UI at `/docs`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/health` | Health check |
| `POST` | `/api/scan/upload` | Endpoint untuk Raspberry Pi mengirim foto |
| `POST` | `/api/analysis/trigger` | Manually trigger energy analysis |

---

## Project Structure

```
Dashboard-AMCS-Replika-BRIN-AI/
│
├── app/Http/Controllers/
│   ├── AiResultController.php      ← Receive & serve AI results
│   ├── BmkgController.php          ← BMKG weather proxy
│   ├── DashboardController.php     ← Dashboard + InfluxDB charts
│   ├── DeviceCommandController.php ← Settings, calibration, MQTT cmds
│   └── PlantPresetController.php   ← Plant preset CRUD
│
├── app/Http/Middleware/
│   └── ValidateAiApiKey.php        ← X-API-Key middleware
│
├── app/Models/
│   ├── DeviceSetting.php           ← Device config (calibration, rules)
│   ├── EnergyAnalysis.php          ← AI energy analysis results
│   ├── PlantPreset.php             ← Plant automation presets
│   └── PlantScan.php               ← YOLO plant scan results
│
├── AIServer/                       ← FastAPI AI Processing Server
│   ├── main.py                     ← App entry + MQTT listener
│   ├── config.py                   ← .env config loader
│   ├── scheduler.py                ← APScheduler periodic tasks
│   ├── services/
│   │   ├── yolo_service.py         ← YOLOv8 inference + capture
│   │   ├── influxdb_service.py     ← Solar data from InfluxDB
│   │   ├── bmkg_service.py         ← BMKG weather forecast
│   │   ├── battery_ai.py           ← Energy math calculations
│   │   └── dashboard_client.py     ← Push results to Dashboard API
│   ├── models/
│   │   └── best.pt                 ← YOLOv8 trained weights
│   ├── requirements.txt
│   └── .env
│
├── resources/views/
│   ├── dashboard.blade.php         ← Main dashboard UI
│   ├── settings.blade.php          ← Settings, calibration, presets
│   └── partials/
│       ├── settings-device.blade.php
│       ├── settings-rules.blade.php
│       └── settings-calibration.blade.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│       └── SmartGardenSeeder.php   ← Default calibration seed
│
├── routes/web.php                  ← All application routes
├── code_amcs.ino                   ← ESP32 firmware source
├── start-dev.ps1                   ← One-click dev startup script
└── README.md
```

---

## Hardware (ESP32)

### Firmware: `code_amcs.ino`

| Feature | Description |
|---------|-------------|
| Sensor reading | ADS1115 ADC for pH, TDS, Turbidity; DS18B20 for water temp; DHT11 for air/humidity |
| Connectivity | WiFi + MQTT over WebSocket Secure (WSS port 443) |
| Calibration storage | `Preferences` library (NVS flash) — survives reboot |
| Telemetry interval | Default 60 seconds (configurable via dashboard) |
| Relay control | 4 relays (R1–R4) for pumps/fan, active LOW |
| Display | ST7735 TFT LCD — shows sensor values + relay status |
| Automation | Pulse & Check algorithm — runs locally on ESP32 using pushed rules |

### Pin Map

| GPIO | Component |
|------|-----------|
| 25 | Relay R1 (pH pump) |
| 26 | Relay R2 (TDS/nutrisi pump) |
| 27 | Relay R3 (water pump) |
| 14 | Relay R4 (fan/spray) |
| 21 | I2C SDA (ADS1115) |
| 22 | I2C SCL (ADS1115) |
| 4  | DS18B20 (water temp) |
| 5  | DHT11 (air temp/humidity) |
| 34 | LDR (light level) |
| 15/16/17 | TFT CS/DC/RST |

---

## Changelog

### V5 — Production Ready & Cleanup (May 2026)
- **Push-based AI Upload**: AI Server tidak lagi me-request kamera, melainkan menerima foto yang di-push oleh script `raspi_sender.py` secara aman via API Key. Fitur manual trigger yang sudah obsolete dihapus total.
- **Docker Environments**: Pemisahan konfigurasi `docker-compose.yml` untuk lingkungan server Coolify dan `docker-compose.yogi.yml` untuk server standalone MySQL.
- **Translasi UI**: Terminologi Dashboard disesuaikan penuh ke Bahasa Indonesia yang rapi (termasuk "Mode Hemat Energi").
- Sinkronisasi endpoint AI Server dengan environment terpadu (`python-dotenv`).

### V4 — Calibration & Safety Enhancements (May 2026)
- Raw sensor values (mV/V) displayed on dashboard below calculated values
- Manual override safety cap: max 2 hours (7200s)
- Cancel running relay by clicking its button again (sends `duration: 0`)
- Calibration fix: values now sent as proper floats to ESP32 (was string type)
- Startup script `start-dev.ps1` for one-click dev environment
- Seeder simplified: calibration-only defaults, no preset data

### V3 — Distributed Architecture (May 17, 2026)
- Separated Dashboard and AI Server into independent processes
- AI Server: FastAPI + APScheduler (embedded, no extra terminal needed)
- REST API with X-API-Key auth between services
- Database tables: `energy_analyses`, `plant_scans`

### V2.x — Solar & Weather (May 2026)
- Multi-bucket InfluxDB (water sensors + solar panel)
- BMKG weather forecast integration
- Smart Battery AI calculations

### V1 — Initial Release (April 2026)
- MQTT real-time monitoring dashboard
- Plant disease detection (file-based)

---

## Team

**Tim Kevin — Bootcamp BRIN 2026**

| Name | Role |
|------|------|
| Kevin Syonin | AI Integration & System Architecture |
| Excel Viryan | Hardware & Sensor Integration |
| Moreno Dwiputra | Frontend Dashboard & MQTT Architecture |

**Supervisor**: BRIN (Badan Riset dan Inovasi Nasional)

---

## License

Built for educational and research purposes under the BRIN Bootcamp 2026 program.
