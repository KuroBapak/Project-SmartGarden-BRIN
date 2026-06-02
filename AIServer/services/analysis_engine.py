"""
Rule-Based Analysis Engine v2 — Multi-Factor Risk Scoring text generation.

Generates informative analysis text using contextual templates based on
the new risk scoring system from battery_ai v2. Provides actionable
insights about energy budget, night survival, and charging projections.
"""
import logging
from datetime import datetime
from services.battery_ai import PANEL_MAX_W, MINIMUM_SAFE_SOC

logger = logging.getLogger(__name__)


def generate_analysis(battery: dict, sensors: dict, forecasts: list) -> str:
    """
    Generate comprehensive energy analysis text from battery AI v2 results.

    Returns a formatted multi-line string with status emoji, risk score,
    and 4-6 actionable bullet points.
    """
    status = battery.get("status", "normal")
    risk_score = battery.get("risk_score", 0)
    net_power = battery.get("net_power", 0)
    solar_power = battery.get("solar_power", 0)
    load_power = battery.get("load_power", 0)
    battery_pct = battery.get("battery_pct", 0)
    endurance = battery.get("endurance_hours")
    solar_forecast = battery.get("solar_forecast", 0)
    can_survive = battery.get("can_survive_night", True)
    time_to_full = battery.get("time_to_full")
    time_to_empty = battery.get("time_to_empty")
    hours_to_sunset = battery.get("hours_to_sunset", 0)
    hours_to_sunrise = battery.get("hours_to_sunrise", 0)
    risk_factors = battery.get("risk_factors", {})

    # ── Time context ──
    hour = datetime.now().hour
    if 6 <= hour < 10:
        time_ctx = "pagi"
    elif 10 <= hour < 15:
        time_ctx = "siang"
    elif 15 <= hour < 18:
        time_ctx = "sore"
    else:
        time_ctx = "malam"
    is_daytime = 6 <= hour < 18

    # ── Weather context ──
    weather_ctx = _analyze_weather(forecasts)

    # ── Sensor context ──
    sensor_ctx = _analyze_sensors(sensors, weather_ctx, is_daytime)

    # ── Build analysis ──
    if status == "emergency":
        return _build_emergency(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx, risk_score, can_survive,
            time_to_full, time_to_empty, hours_to_sunrise, risk_factors
        )
    elif status == "hoarding":
        return _build_hoarding(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx, risk_score, can_survive,
            time_to_full, time_to_empty, hours_to_sunset, hours_to_sunrise
        )
    else:
        return _build_normal(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx, risk_score, can_survive,
            time_to_full, time_to_empty, hours_to_sunset
        )


def _analyze_weather(forecasts: list) -> dict:
    """Extract weather context from BMKG forecast data."""
    ctx = {
        "has_rain": False,
        "has_storm": False,
        "rain_count": 0,
        "storm_count": 0,
        "avg_cloud": 50,
        "avg_temp": 28,
        "dominant_weather": "Cerah",
        "summary": "Cuaca cerah",
    }

    if not forecasts:
        return ctx

    clouds = []
    temps = []
    weather_codes = []

    for f in forecasts:
        if "tcc" in f:
            clouds.append(f["tcc"])
        if "t" in f:
            temps.append(f["t"])
        wcode = f.get("weather", 0)
        weather_codes.append(wcode)
        if wcode >= 60:
            ctx["rain_count"] += 1
            ctx["has_rain"] = True
        if wcode >= 95:
            ctx["storm_count"] += 1
            ctx["has_storm"] = True

    if clouds:
        ctx["avg_cloud"] = round(sum(clouds) / len(clouds), 1)
    if temps:
        ctx["avg_temp"] = round(sum(temps) / len(temps), 1)

    # Determine dominant weather description
    if ctx["has_storm"]:
        ctx["dominant_weather"] = "Badai/Hujan Lebat"
        ctx["summary"] = f"Diprediksi {ctx['storm_count']} periode badai dalam 24 jam ke depan"
    elif ctx["has_rain"]:
        ctx["dominant_weather"] = "Hujan"
        ctx["summary"] = f"Diprediksi {ctx['rain_count']} periode hujan dalam 24 jam ke depan"
    elif ctx["avg_cloud"] > 70:
        ctx["dominant_weather"] = "Berawan Tebal"
        ctx["summary"] = f"Langit berawan tebal (tutupan awan {ctx['avg_cloud']:.0f}%)"
    elif ctx["avg_cloud"] > 40:
        ctx["dominant_weather"] = "Berawan Sebagian"
        ctx["summary"] = f"Berawan sebagian (tutupan awan {ctx['avg_cloud']:.0f}%)"
    else:
        ctx["dominant_weather"] = "Cerah"
        ctx["summary"] = f"Cuaca cerah (tutupan awan {ctx['avg_cloud']:.0f}%), output solar optimal"

    return ctx


def _analyze_sensors(sensors: dict, weather_ctx: dict, is_daytime: bool) -> dict:
    """Extract sensor context for recommendations and cross-reference with weather."""
    ctx = {
        "has_data": len(sensors) > 0,
        "alerts": [],
        "summary_points": [],
    }

    if not sensors:
        return ctx

    # Cross-reference light sensor vs BMKG Forecast (only during daytime)
    light = sensors.get("light")
    if light is not None and is_daytime:
        avg_cloud = weather_ctx.get("avg_cloud", 50)

        # Rule 1: False Negative BMKG (Forecast says cloudy, but it's bright)
        if avg_cloud > 70 and light > 70:
            ctx["alerts"].insert(0, f"Informasi: Prediksi BMKG berawan tebal ({avg_cloud}%), tapi sensor mendeteksi cahaya sangat terang ({light}%). Produksi solar mungkin lebih baik dari perkiraan.")
        # Rule 2: False Positive BMKG (Forecast says clear, but it's dark)
        elif avg_cloud < 30 and light < 30:
            ctx["alerts"].insert(0, f"Peringatan: Prediksi BMKG cerah ({avg_cloud}%), tapi sensor mendeteksi kondisi mendung/gelap ({light}%). Waspada penurunan daya solar mendadak.")
        else:
            ctx["summary_points"].append(f"Intensitas cahaya {light}% (sesuai prediksi cuaca)")

    return ctx


# ═══════════════════════════════════════════════════════════════════
# Status Template Builders
# ═══════════════════════════════════════════════════════════════════

def _build_normal(net_power, solar_power, load_power, battery_pct,
                  endurance, solar_forecast, time_ctx, is_daytime,
                  weather_ctx, sensor_ctx, risk_score, can_survive,
                  time_to_full, time_to_empty, hours_to_sunset) -> str:
    """Build concise analysis text for NORMAL status."""
    lines = [f"🟢 Sistem Normal (Risk Score: {risk_score:.0%}):"]

    lines.append(f"• 🔋 Baterai {battery_pct:.0f}%, Net Daya {net_power:+.1f}W.")

    if time_to_full is not None:
        lines.append(f"• ⏱️ Estimasi penuh: {_format_hours(time_to_full)}")
    elif endurance is not None:
        lines.append(f"• ⏱️ Estimasi daya tahan: {_format_hours(endurance)}")

    if is_daytime:
        lines.append(f"• 🌤️ Solar aktif {solar_power}W. Cuaca: {weather_ctx['dominant_weather']}.")
    else:
        lines.append(f"• 🌙 Malam hari. Prediksi cuaca besok: {weather_ctx['dominant_weather']}.")

    if sensor_ctx["alerts"]:
        lines.append(f"• ⚠️ {sensor_ctx['alerts'][0]}")

    lines.append("• ✅ Sistem stabil, semua aktuator beroperasi penuh.")
    return "\n".join(lines)


def _build_hoarding(net_power, solar_power, load_power, battery_pct,
                    endurance, solar_forecast, time_ctx, is_daytime,
                    weather_ctx, sensor_ctx, risk_score, can_survive,
                    time_to_full, time_to_empty, hours_to_sunset, hours_to_sunrise) -> str:
    """Build concise analysis text for HOARDING status."""
    lines = [f"🟡 Mode Hemat Energi (Risk Score: {risk_score:.0%}):"]

    lines.append(f"• 🔋 Baterai {battery_pct:.0f}%, Net Daya {net_power:+.1f}W.")

    if not can_survive:
        lines.append(f"• ⚠️ Energi berisiko tak cukup untuk malam ini.")
    elif weather_ctx["has_rain"]:
        lines.append(f"• ⚠️ Cuaca {weather_ctx['dominant_weather'].lower()}, output solar akan menurun.")
    elif battery_pct < 50:
        lines.append(f"• ⚠️ Baterai di bawah batas ideal, perlu pengisian.")

    if time_to_empty is not None:
        lines.append(f"• ⏱️ Estimasi baterai habis: {_format_hours(time_to_empty)}")

    lines.append("• ⚡ AKSI: Kurangi frekuensi pompa dosing (pH/TDS), pertahankan sirkulasi air utama.")

    for alert in sensor_ctx["alerts"][:1]:
        lines.append(f"• ⚠️ {alert}")

    return "\n".join(lines)


def _build_emergency(net_power, solar_power, load_power, battery_pct,
                     endurance, solar_forecast, time_ctx, is_daytime,
                     weather_ctx, sensor_ctx, risk_score, can_survive,
                     time_to_full, time_to_empty, hours_to_sunrise,
                     risk_factors) -> str:
    """Build concise analysis text for EMERGENCY status."""
    lines = [f"🔴 Mode Darurat Aktif (Risk Score: {risk_score:.0%}):"]

    if battery_pct < MINIMUM_SAFE_SOC:
        lines.append(f"• KRITIS: Baterai {battery_pct:.1f}% (Batas {MINIMUM_SAFE_SOC}%). Net daya: {net_power:+.1f}W.")
    elif not can_survive:
        lines.append(f"• KRITIS: Energi tak cukup untuk bertahan hingga pagi. Net daya: {net_power:+.1f}W.")
    else:
        lines.append(f"• KRITIS: Kondisi energi & cuaca buruk. Net daya: {net_power:+.1f}W.")

    if time_to_empty is not None:
        lines.append(f"• ⏱️ Estimasi baterai habis: {_format_hours(time_to_empty)}")
    elif time_to_full is not None and battery_pct < 50:
        lines.append(f"• ⏱️ Estimasi aman (50%): {_format_hours(time_to_full * (50 - battery_pct) / max(100 - battery_pct, 1))}")

    lines.append("• ⚡ AKSI: Hentikan pompa dosing & kipas! Prioritaskan sisa daya untuk pompa air utama.")

    if is_daytime:
        lines.append(f"• 🌤️ Info: Solar {solar_power}W (Prediksi {solar_forecast}W). Cuaca: {weather_ctx['dominant_weather'].lower()}.")
    else:
        lines.append(f"• 🌙 Info: Tunggu pagi ({_format_hours(hours_to_sunrise)} lagi) untuk solar recovery.")

    return "\n".join(lines)


# ═══════════════════════════════════════════════════════════════════
# Helpers
# ═══════════════════════════════════════════════════════════════════

def _format_hours(hours: float) -> str:
    """Format hours into human-readable Indonesian string."""
    if hours is None:
        return "N/A"
    if hours >= 72:
        days = hours / 24
        return f"{days:.0f} hari"
    elif hours >= 24:
        days = int(hours // 24)
        remaining_hours = int(hours % 24)
        if remaining_hours > 0:
            return f"{days} hari {remaining_hours} jam"
        return f"{days} hari"
    elif hours >= 1:
        h = int(hours)
        m = int((hours - h) * 60)
        if m > 0:
            return f"{h} jam {m} menit"
        return f"{h} jam"
    else:
        minutes = hours * 60
        return f"{minutes:.0f} menit"
