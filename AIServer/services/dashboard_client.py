"""Dashboard API Client — push AI results to the Dashboard server."""
import httpx
import logging
import os
from config import DASHBOARD_URL, AI_SERVER_API_KEY

logger = logging.getLogger(__name__)
HEADERS = {"X-API-Key": AI_SERVER_API_KEY, "Accept": "application/json"}


async def push_energy_analysis(payload: dict):
    """POST energy analysis results to Dashboard."""
    url = f"{DASHBOARD_URL}/api/ai/energy-analysis"
    try:
        async with httpx.AsyncClient(timeout=15.0) as client:
            resp = await client.post(url, json=payload, headers=HEADERS)
            if resp.status_code == 201:
                logger.info("✅ Energy analysis pushed to Dashboard")
            else:
                logger.warning(f"Dashboard rejected energy push: {resp.status_code} - {resp.text}")
    except Exception as e:
        logger.error(f"Failed to push energy analysis: {e}")


async def push_plant_scan(scan_data: dict, annotated_path: str = None, original_path: str = None):
    """POST plant scan results + images to Dashboard."""
    url = f"{DASHBOARD_URL}/api/ai/plant-scan"
    try:
        files = {}
        if annotated_path and os.path.exists(annotated_path):
            files["image"] = ("annotated.jpg", open(annotated_path, "rb"), "image/jpeg")
        if original_path and os.path.exists(original_path):
            files["image_original"] = ("original.jpg", open(original_path, "rb"), "image/jpeg")

        form_data = {k: str(v) if not isinstance(v, (list, dict)) else "" for k, v in scan_data.items()}
        # Send detections as JSON string — Dashboard will parse it
        import json
        if "detections" in scan_data:
            form_data["detections"] = json.dumps(scan_data["detections"])

        async with httpx.AsyncClient(timeout=30.0) as client:
            resp = await client.post(url, data=form_data, files=files, headers=HEADERS)
            if resp.status_code == 201:
                logger.info("✅ Plant scan pushed to Dashboard")
            else:
                logger.warning(f"Dashboard rejected scan push: {resp.status_code} - {resp.text}")

        # Cleanup temp files
        for path in [annotated_path, original_path]:
            if path and os.path.exists(path):
                try:
                    os.remove(path)
                except OSError:
                    pass
    except Exception as e:
        logger.error(f"Failed to push plant scan: {e}")
