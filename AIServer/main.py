"""
SmartGarden BRIN — AI Server
FastAPI application for AI processing (YOLO plant disease, rule-based energy analysis).
Runs as a Docker container, pushes results to the Dashboard.

Usage:
    pip install -r requirements.txt
    uvicorn main:app --host 0.0.0.0 --port 8001
"""
import asyncio
import json
import logging
import threading

import cv2
import numpy as np

from fastapi import FastAPI, Header, HTTPException, File, UploadFile
from contextlib import asynccontextmanager
from apscheduler.schedulers.asyncio import AsyncIOScheduler

import config
import scheduler

# ── Logging ──
logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(name)s] %(levelname)s: %(message)s")
logger = logging.getLogger("ai-server")

# ── MQTT sensor listener (background thread) ──
mqtt_thread = None


def start_mqtt_listener():
    """Subscribe to MQTT sensor topic and update scheduler.latest_sensors."""
    try:
        import paho.mqtt.client as mqtt_lib

        def on_connect(client, userdata, flags, rc):
            if rc == 0:
                logger.info("MQTT connected — subscribing to sensor telemetry")
                client.subscribe("brin/water/+/up/telemetry")
            else:
                logger.warning(f"MQTT connect failed: rc={rc}")

        def on_message(client, userdata, msg):
            try:
                data = json.loads(msg.payload.decode())
                for key in ["water_temp", "air_temp", "humidity", "ph", "tds", "turbidity", "light"]:
                    if key in data:
                        scheduler.latest_sensors[key] = data[key]
            except Exception:
                pass

        # Determine if we should use WebSockets based on the Dashboard rules
        use_ws = False
        port = config.MQTT_PORT

        # If it's the online broker without a WS port, it uses WSS on 443
        if not config.MQTT_WS_PORT and ("kurobapak.site" in config.MQTT_HOST):
            use_ws = True
            port = 443
        elif config.MQTT_WS_PORT:
            # Local mode with explicit WS port
            use_ws = True
            port = int(config.MQTT_WS_PORT)

        if use_ws:
            client = mqtt_lib.Client(client_id="AIServer-Sensor-Sub", transport="websockets")
            client.ws_set_options(path="/mqtt")
            if port == 443:
                client.tls_set()
        else:
            client = mqtt_lib.Client(client_id="AIServer-Sensor-Sub")

        if config.MQTT_USERNAME:
            client.username_pw_set(config.MQTT_USERNAME, config.MQTT_PASSWORD)

        client.connect(config.MQTT_HOST, port, keepalive=60)

        client.on_connect = on_connect
        client.on_message = on_message
        client.loop_forever()
    except Exception as e:
        logger.error(f"MQTT listener error: {e}")


# ── App lifecycle ──
sched = AsyncIOScheduler()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup/shutdown lifecycle."""
    logger.info("=" * 50)
    logger.info("🚀 SmartGarden BRIN — AI Server starting")
    logger.info(f"   Dashboard URL: {config.DASHBOARD_URL}")
    logger.info(f"   Analysis engine: risk-scoring-v2")
    logger.info(f"   YOLO model: {config.YOLO_MODEL_PATH}")
    logger.info(f"   Analysis interval: {config.ANALYSIS_INTERVAL}s")
    logger.info("=" * 50)

    # Start MQTT listener in background thread
    global mqtt_thread
    mqtt_thread = threading.Thread(target=start_mqtt_listener, daemon=True)
    mqtt_thread.start()

    # Schedule periodic tasks
    sched.add_job(scheduler.run_energy_analysis, "interval", seconds=config.ANALYSIS_INTERVAL,
                  id="energy_analysis")
                  
    sched.start()

    # Run first analysis after 10 seconds (let MQTT settle)
    async def initial_run():
        await asyncio.sleep(10)
        await scheduler.run_energy_analysis()

    asyncio.create_task(initial_run())

    yield

    sched.shutdown(wait=False)
    logger.info("AI Server shutting down")


# ── FastAPI App ──
app = FastAPI(title="SmartGarden BRIN AI Server", version="1.0.0", lifespan=lifespan)


def verify_api_key(x_api_key: str = Header(None)):
    if not x_api_key or x_api_key != config.AI_SERVER_API_KEY:
        raise HTTPException(status_code=401, detail="Invalid API key")


@app.get("/health")
async def health():
    return {"status": "ok", "analysis_engine": "rule-based-v1", "sensors": len(scheduler.latest_sensors) > 0}



@app.post("/api/analysis/trigger")
async def trigger_analysis(x_api_key: str = Header(None)):
    """Manually trigger an energy analysis cycle."""
    verify_api_key(x_api_key)
    asyncio.create_task(scheduler.run_energy_analysis())
    return {"triggered": True, "message": "Energy analysis started"}


@app.post("/api/scan/upload")
async def upload_scan(file: UploadFile = File(...), x_api_key: str = Header(None)):
    """Receive an image file from Raspberry Pi for YOLO processing."""
    verify_api_key(x_api_key)
    
    if not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File must be an image")

    # Read image bytes
    contents = await file.read()
    
    # Convert bytes to OpenCV frame
    nparr = np.frombuffer(contents, np.uint8)
    frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    
    if frame is None:
        raise HTTPException(status_code=400, detail="Could not decode image")

    # Process frame asynchronously to avoid blocking the HTTP response too long
    # Or await it to return the result immediately. Let's run it async in background.
    asyncio.create_task(scheduler.process_frame(frame, source="raspi_upload"))
    
    return {"uploaded": True, "message": "Image received and queued for processing"}
