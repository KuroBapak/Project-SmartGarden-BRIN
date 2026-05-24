"""
AI Server Scheduler — periodic tasks for energy analysis and plant scanning.
"""
import asyncio
import logging
from datetime import datetime
from services import analysis_engine, yolo_service, influxdb_service, bmkg_service, battery_ai, dashboard_client

logger = logging.getLogger(__name__)

# Store latest sensor data from MQTT (updated by mqtt subscriber in main.py)
latest_sensors = {}


async def run_energy_analysis():
    """Collect data → Rule-based analysis → Battery AI → push to Dashboard."""
    logger.info("⚡ Starting energy analysis cycle...")
    try:
        # 1. Fetch solar data from InfluxDB
        solar = influxdb_service.fetch_solar_data()

        # 2. Fetch BMKG weather
        bmkg = await bmkg_service.fetch_forecast()
        forecasts = bmkg_service.extract_forecasts_flat(bmkg) if bmkg else []

        # 3. Run battery AI calculations
        battery = battery_ai.calculate(solar, forecasts)

        # 4. Generate analysis text (rule-based engine)
        analysis_text = analysis_engine.generate_analysis(battery, latest_sensors, forecasts)

        # 5. Push to Dashboard
        payload = {
            "analysis_text": analysis_text,
            "status": battery["status"],
            "model": "rule-based-engine-v1",
            "net_power": battery["net_power"],
            "solar_power": battery["solar_power"],
            "load_power": battery["load_power"],
            "battery_pct": battery["battery_pct"],
            "endurance_hours": battery["endurance_hours"],
            "solar_forecast": battery["solar_forecast"],
            "raw_data": {"solar": solar, "sensors": latest_sensors},
        }
        await dashboard_client.push_energy_analysis(payload)
        logger.info(f"⚡ Energy analysis complete: {battery['status']}")

    except Exception as e:
        logger.error(f"Energy analysis failed: {e}", exc_info=True)



async def process_frame(frame, source: str = "upload"):
    """Run YOLO inference on a provided frame and push to Dashboard."""
    try:
        detections, annotated, original = yolo_service.run_detection(frame)
        status = yolo_service.determine_status(detections)

        # Save temp images
        annotated_path = yolo_service.save_temp_image(annotated)
        original_path = yolo_service.save_temp_image(original)

        scan_data = {
            "status": status["status"],
            "status_label": status["status_label"],
            "status_emoji": status["status_emoji"],
            "message": status["message"],
            "detections": status.get("diseases", []),
            "total_detections": len(detections),
            "scan_source": source,
        }

        await dashboard_client.push_plant_scan(scan_data, annotated_path, original_path)
        logger.info(f"🌿 Scan complete: {status['status_emoji']} {status['status_label']}")

    except Exception as e:
        logger.error(f"Frame processing failed: {e}", exc_info=True)


# NOTE: build_energy_prompt() removed — replaced by analysis_engine.generate_analysis()
