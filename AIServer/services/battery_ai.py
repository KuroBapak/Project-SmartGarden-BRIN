"""Smart Battery AI — energy calculations ported from dashboard JS."""
from datetime import datetime, timedelta

PANEL_MAX_W = 50  # Max solar panel output at 0% cloud
BATTERY_CAPACITY_WH = 240  # 12V 20Ah


def calculate(solar_data: dict, forecasts: list) -> dict:
    """Calculate battery AI metrics. Returns status + metrics."""
    solar_w = solar_data.get("pv_power", 0)
    load_w = solar_data.get("load_power", 0)
    battery_pct = solar_data.get("battery_percentage", 0)

    net_power = solar_w - load_w

    # Predict solar output from cloud cover (next 24h daytime)
    now = datetime.now()
    cutoff = now + timedelta(hours=24)
    next_24h = []
    for f in forecasts:
        try:
            fdt = datetime.fromisoformat(f.get("local_datetime", "").replace(" ", "T"))
            if now < fdt <= cutoff:
                next_24h.append(f)
        except (ValueError, TypeError):
            continue

    daytime = [f for f in next_24h if 6 <= datetime.fromisoformat(
        f["local_datetime"].replace(" ", "T")).hour < 18]

    avg_cloud = 50
    if daytime:
        avg_cloud = sum(f.get("tcc", 50) for f in daytime) / len(daytime)

    predicted_solar = PANEL_MAX_W * (1 - avg_cloud / 100)

    # Bad weather detection
    bad_weather = [f for f in next_24h if f.get("weather", 0) >= 60]
    storm_weather = [f for f in next_24h if f.get("weather", 0) >= 95]
    has_bad = len(bad_weather) > 0
    has_storm = len(storm_weather) > 0

    # Endurance calculation
    worst_solar = predicted_solar * 0.3 if has_bad else predicted_solar
    deficit = load_w - worst_solar
    if deficit > 0:
        battery_wh = BATTERY_CAPACITY_WH * (battery_pct / 100)
        endurance = battery_wh / deficit
    else:
        endurance = 99999  # effectively infinite

    # Determine status
    if has_storm or (has_bad and endurance < 12):
        status = "emergency"
    elif has_bad or endurance < 48:
        status = "hoarding"
    else:
        status = "normal"

    return {
        "status": status,
        "net_power": round(net_power, 2),
        "solar_power": round(solar_w, 2),
        "load_power": round(load_w, 2),
        "battery_pct": round(battery_pct, 2),
        "endurance_hours": round(endurance, 2) if endurance < 99999 else None,
        "solar_forecast": round(predicted_solar, 2),
    }
