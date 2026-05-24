import os
from dotenv import load_dotenv

load_dotenv()

# Dashboard
DASHBOARD_URL       = os.getenv("DASHBOARD_URL")
AI_SERVER_API_KEY   = os.getenv("AI_SERVER_API_KEY")

# YOLO
YOLO_MODEL_PATH     = os.getenv("YOLO_MODEL_PATH")
YOLO_CONFIDENCE     = float(os.getenv("YOLO_CONFIDENCE"))

# InfluxDB
INFLUXDB_URL        = os.getenv("INFLUXDB_URL")
INFLUXDB_TOKEN      = os.getenv("INFLUXDB_TOKEN")
INFLUXDB_ORG        = os.getenv("INFLUXDB_ORG")
INFLUXDB_BUCKET_SOLAR = os.getenv("INFLUXDB_BUCKET_SOLAR")

# BMKG
BMKG_ADM4           = os.getenv("BMKG_ADM4")

# MQTT
MQTT_HOST           = os.getenv("MQTT_HOST")
MQTT_PORT           = int(os.getenv("MQTT_PORT") or "1883")
MQTT_WS_PORT        = os.getenv("MQTT_WS_PORT")
MQTT_USERNAME       = os.getenv("MQTT_USERNAME")
MQTT_PASSWORD       = os.getenv("MQTT_PASSWORD")

# Scheduler
ANALYSIS_INTERVAL   = int(os.getenv("ANALYSIS_INTERVAL"))
