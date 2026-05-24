"""BMKG Weather Forecast Service."""
import httpx
import logging
from config import BMKG_ADM4

logger = logging.getLogger(__name__)
BMKG_API_URL = f"https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={BMKG_ADM4}"


async def fetch_forecast() -> dict | None:
    """Fetch weather forecast from BMKG API."""
    try:
        async with httpx.AsyncClient(timeout=15.0, verify=False) as client:
            resp = await client.get(BMKG_API_URL)
            if resp.status_code == 200:
                return resp.json()
            logger.warning(f"BMKG API error: {resp.status_code}")
    except Exception as e:
        logger.error(f"BMKG fetch error: {e}")
    return None


def extract_forecasts_flat(bmkg_data: dict) -> list:
    """Flatten all forecast entries from BMKG response."""
    try:
        cuaca = bmkg_data.get("data", [{}])[0].get("cuaca", [])
        return [item for day in cuaca for item in day]
    except (IndexError, AttributeError):
        return []
