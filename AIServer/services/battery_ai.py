"""
Smart Battery AI — Multi-Factor Risk Scoring Engine v2.

Replaces the naive rule-based approach with a weighted risk scoring system
that considers: Battery SOC, Energy Budget (time-aware), Weather forecast,
Net Power state, and Time-of-Day context.

Hardware specs:
    Panel: 50W max (monocrystalline)
    Battery: 12V 20Ah Lead-Acid (240Wh usable)
    Load: ~12-18W (pumps, aerator, ESP32, sensors)
"""
import logging
from datetime import datetime, timedelta

logger = logging.getLogger("battery_ai")

# ── Hardware Constants ──
PANEL_MAX_W = 50          # Max solar panel output at 0% cloud cover
BATTERY_CAPACITY_WH = 240  # 12V × 20Ah
MINIMUM_SAFE_SOC = 20      # Below this → instant emergency (lead-acid DoD protection)

# ── Time Constants (Cibinong, Jawa Barat) ──
SUNRISE_HOUR = 6
SUNSET_HOUR = 18

# ── Risk Weights ──
WEIGHT_SOC = 0.35
WEIGHT_ENERGY_BUDGET = 0.25
WEIGHT_WEATHER = 0.20
WEIGHT_NET_POWER = 0.10
WEIGHT_TIME = 0.10


def calculate(solar_data: dict, forecasts: list) -> dict:
    """
    Calculate battery AI metrics using multi-factor risk scoring.

    Returns dict with status, metrics, risk_score, and energy projections.
    """
    solar_w = solar_data.get("pv_power", 0)
    load_w = solar_data.get("load_power", 0)
    battery_pct = solar_data.get("battery_percentage", 0)
    net_power = solar_w - load_w
    now = datetime.now()
    hour = now.hour

    logger.debug(f"INPUT: pv={solar_w}W, load={load_w}W, net={net_power}W, bat={battery_pct}%, hour={hour}")

    # ── 1. Predict solar from BMKG cloud cover ──
    predicted_solar, weather_risk, has_bad, has_storm = _analyze_forecasts(forecasts, now)

    # ── 2. Calculate all risk factors ──
    soc_risk = _soc_risk(battery_pct)
    energy_budget = _energy_budget(battery_pct, net_power, load_w, predicted_solar, hour)
    budget_risk = energy_budget["risk"]
    net_risk = _net_power_risk(net_power, load_w)
    time_risk = _time_risk(hour)

    # ── 3. Weighted risk score ──
    risk_score = (
        WEIGHT_SOC * soc_risk +
        WEIGHT_ENERGY_BUDGET * budget_risk +
        WEIGHT_WEATHER * weather_risk +
        WEIGHT_NET_POWER * net_risk +
        WEIGHT_TIME * time_risk
    )
    risk_score = round(min(1.0, max(0.0, risk_score)), 3)

    logger.debug(
        f"RISKS: soc={soc_risk:.2f}, budget={budget_risk:.2f}, "
        f"weather={weather_risk:.2f}, net={net_risk:.2f}, time={time_risk:.2f} → score={risk_score:.3f}"
    )

    # ── 4. Determine status ──
    # Hard override: battery below minimum safe SOC → always emergency
    if battery_pct < MINIMUM_SAFE_SOC:
        status = "emergency"
        logger.info(f"STATUS: emergency (battery {battery_pct}% < {MINIMUM_SAFE_SOC}% minimum)")
    elif risk_score >= 0.6:
        status = "emergency"
    elif risk_score >= 0.3:
        status = "hoarding"
    else:
        status = "normal"

    # ── 5. Build endurance (context-aware) ──
    endurance_hours = _smart_endurance(battery_pct, net_power, load_w, solar_w)

    return {
        "status": status,
        "risk_score": risk_score,
        "net_power": round(net_power, 2),
        "solar_power": round(solar_w, 2),
        "load_power": round(load_w, 2),
        "battery_pct": round(battery_pct, 2),
        "endurance_hours": endurance_hours,
        "solar_forecast": round(predicted_solar, 2),
        "can_survive_night": energy_budget["can_survive_night"],
        "time_to_full": energy_budget["time_to_full"],
        "time_to_empty": energy_budget["time_to_empty"],
        "hours_to_sunset": energy_budget["hours_to_sunset"],
        "hours_to_sunrise": energy_budget["hours_to_sunrise"],
        "risk_factors": {
            "soc": round(soc_risk, 2),
            "energy_budget": round(budget_risk, 2),
            "weather": round(weather_risk, 2),
            "net_power": round(net_risk, 2),
            "time": round(time_risk, 2),
        },
    }


# ═══════════════════════════════════════════════════════════════════
# Risk Factor Functions
# ═══════════════════════════════════════════════════════════════════

def _soc_risk(battery_pct: float) -> float:
    """
    Non-linear SOC risk curve.
    More aggressive at low SOC, gentle at high SOC.

      0-10%  → 1.0  (critical)
     10-20%  → 0.8-1.0
     20-40%  → 0.4-0.8
     40-60%  → 0.15-0.4
     60-80%  → 0.05-0.15
     80-100% → 0.0-0.05
    """
    if battery_pct <= 0:
        return 1.0
    if battery_pct >= 100:
        return 0.0

    # Quadratic curve: risk = (1 - pct/100)^2 with floor adjustments
    base = (1.0 - battery_pct / 100.0) ** 1.5

    # Boost risk sharply below 20%
    if battery_pct < 20:
        base = max(base, 0.8 + (20 - battery_pct) / 100)

    return min(1.0, base)


def _energy_budget(battery_pct: float, net_power: float, load_w: float,
                   predicted_solar: float, hour: int) -> dict:
    """
    Time-aware energy budget projection.

    Calculates whether the system can survive through the night
    and how long until battery is full or empty.
    """
    battery_wh = BATTERY_CAPACITY_WH * (battery_pct / 100.0)
    battery_remaining_to_full = BATTERY_CAPACITY_WH - battery_wh
    is_daytime = SUNRISE_HOUR <= hour < SUNSET_HOUR

    # Hours until sunset/sunrise
    if is_daytime:
        hours_to_sunset = max(0, SUNSET_HOUR - hour)
        hours_to_sunrise = hours_to_sunset + (24 - SUNSET_HOUR + SUNRISE_HOUR)  # next day
    else:
        hours_to_sunset = 0  # already past sunset
        if hour >= SUNSET_HOUR:
            hours_to_sunrise = (24 - hour) + SUNRISE_HOUR
        else:
            hours_to_sunrise = SUNRISE_HOUR - hour

    # Night duration from now (hours of darkness remaining)
    dark_hours = hours_to_sunrise if not is_daytime else max(0, SUNSET_HOUR - SUNRISE_HOUR - hours_to_sunset + 12)

    # Energy needed to survive the night (load only, no solar)
    energy_needed_night = load_w * (hours_to_sunrise if not is_daytime else (24 - SUNSET_HOUR + SUNRISE_HOUR))

    # Can we survive the night?
    can_survive_night = battery_wh >= energy_needed_night * 0.8  # 80% margin

    # Time to empty (worst case: no solar)
    if load_w > 0:
        time_to_empty = battery_wh / load_w
    else:
        time_to_empty = None  # no load

    # Time to full (if charging)
    if net_power > 0:
        time_to_full = battery_remaining_to_full / net_power
    else:
        time_to_full = None  # not charging

    # Calculate budget risk
    if battery_pct < MINIMUM_SAFE_SOC:
        risk = 1.0
    elif not is_daytime:
        # Night: risk based on whether we can survive until sunrise
        if time_to_empty is not None and time_to_empty < hours_to_sunrise:
            risk = min(1.0, (hours_to_sunrise - time_to_empty) / hours_to_sunrise + 0.3)
        elif can_survive_night:
            risk = 0.1
        else:
            risk = 0.7
    else:
        # Daytime: risk based on whether we're building enough reserves
        if net_power >= 0:
            # Charging — check if we'll have enough by sunset
            projected_wh_at_sunset = battery_wh + (net_power * hours_to_sunset)
            night_energy = load_w * (24 - SUNSET_HOUR + SUNRISE_HOUR)  # ~12 hours of night
            if projected_wh_at_sunset >= night_energy:
                risk = 0.0
            else:
                risk = min(1.0, 1.0 - (projected_wh_at_sunset / max(night_energy, 1)))
        else:
            # Deficit during daytime — bad
            if time_to_empty is not None and time_to_empty < 6:
                risk = 0.9
            else:
                risk = 0.5

    return {
        "risk": min(1.0, max(0.0, risk)),
        "can_survive_night": can_survive_night,
        "time_to_full": round(time_to_full, 2) if time_to_full is not None else None,
        "time_to_empty": round(time_to_empty, 2) if time_to_empty is not None else None,
        "hours_to_sunset": round(hours_to_sunset, 1),
        "hours_to_sunrise": round(hours_to_sunrise, 1),
        "energy_needed_night": round(energy_needed_night, 1),
    }


def _analyze_forecasts(forecasts: list, now: datetime) -> tuple:
    """
    Analyze BMKG forecasts and return predicted solar + weather risk.

    Returns: (predicted_solar_w, weather_risk, has_bad, has_storm)
    """
    cutoff = now + timedelta(hours=24)
    next_24h = []

    for f in forecasts:
        try:
            fdt = datetime.fromisoformat(f.get("local_datetime", "").replace(" ", "T"))
            if now < fdt <= cutoff:
                next_24h.append(f)
        except (ValueError, TypeError):
            continue

    # Filter daytime forecasts for solar prediction
    daytime = []
    for f in next_24h:
        try:
            fdt = datetime.fromisoformat(f["local_datetime"].replace(" ", "T"))
            if SUNRISE_HOUR <= fdt.hour < SUNSET_HOUR:
                daytime.append(f)
        except (ValueError, TypeError):
            continue

    # Average cloud cover
    avg_cloud = 50
    if daytime:
        avg_cloud = sum(f.get("tcc", 50) for f in daytime) / len(daytime)

    predicted_solar = PANEL_MAX_W * (1 - avg_cloud / 100)

    # Weather analysis
    bad_weather = [f for f in next_24h if f.get("weather", 0) >= 60]
    storm_weather = [f for f in next_24h if f.get("weather", 0) >= 95]
    has_bad = len(bad_weather) > 0
    has_storm = len(storm_weather) > 0

    # Weather risk scoring
    if has_storm:
        weather_risk = 1.0
    elif has_bad:
        # Scale by how many periods have bad weather
        bad_ratio = len(bad_weather) / max(len(next_24h), 1)
        weather_risk = 0.4 + (bad_ratio * 0.5)  # 0.4 to 0.9
    elif avg_cloud > 70:
        weather_risk = 0.3
    elif avg_cloud > 40:
        weather_risk = 0.15
    else:
        weather_risk = 0.0

    return predicted_solar, min(1.0, weather_risk), has_bad, has_storm


def _net_power_risk(net_power: float, load_w: float) -> float:
    """Risk based on current power balance."""
    if load_w <= 0:
        return 0.0

    ratio = net_power / max(load_w, 1)  # positive = surplus, negative = deficit

    if ratio >= 1.0:
        return 0.0    # Surplus >= load → no risk
    elif ratio >= 0.5:
        return 0.1    # Good surplus
    elif ratio >= 0:
        return 0.2    # Small surplus
    elif ratio >= -0.5:
        return 0.5    # Small deficit
    elif ratio >= -1.0:
        return 0.7    # Full deficit (no solar)
    else:
        return 1.0    # Deficit exceeds load (impossible normally, but safe)


def _time_risk(hour: int) -> float:
    """
    Time-of-day risk. Higher risk as we approach/are in nighttime.
    Solar can't help during the night.
    """
    if 10 <= hour < 14:
        return 0.0    # Peak solar hours
    elif 8 <= hour < 10 or 14 <= hour < 16:
        return 0.1    # Good solar
    elif 6 <= hour < 8 or 16 <= hour < 17:
        return 0.2    # Marginal solar
    elif 17 <= hour < 18:
        return 0.4    # Solar fading
    elif 18 <= hour < 20:
        return 0.6    # Just after sunset
    elif 20 <= hour < 23:
        return 0.7    # Deep night
    elif hour >= 23 or hour < 3:
        return 0.8    # Midnight
    else:
        return 0.5    # 3-6 AM, approaching dawn


def _smart_endurance(battery_pct: float, net_power: float,
                     load_w: float, solar_w: float) -> float | None:
    """
    Context-aware endurance calculation.

    - Surplus: returns hours until battery is 100%
    - Deficit: returns hours until battery is 0%
    - Zero load: returns None
    """
    battery_wh = BATTERY_CAPACITY_WH * (battery_pct / 100.0)

    if net_power > 0:
        # Charging — how long until full?
        remaining = BATTERY_CAPACITY_WH - battery_wh
        if remaining <= 0:
            return None  # Already full
        hours = remaining / net_power
        return round(hours, 2)
    elif net_power < 0:
        # Discharging — how long until empty?
        deficit = abs(net_power)
        if battery_wh <= 0:
            return 0.0  # Already empty
        hours = battery_wh / deficit
        return round(hours, 2)
    else:
        # Perfectly balanced
        return None
