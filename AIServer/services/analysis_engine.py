"""
Rule-Based Analysis Engine — replaces Ollama LLM for energy analysis.

Generates informative, varied, and stable analysis text using contextual
templates based on battery status, weather conditions, solar output,
and sensor data. Zero external dependencies, instant response.
"""
import logging
from datetime import datetime

logger = logging.getLogger(__name__)


def generate_analysis(battery: dict, sensors: dict, forecasts: list) -> str:
    """
    Generate a comprehensive energy analysis text from battery AI results,
    sensor data, and weather forecasts.

    Returns a formatted multi-line string with status emoji, label, and
    3-5 actionable bullet points.
    """
    status = battery.get("status", "normal")
    net_power = battery.get("net_power", 0)
    solar_power = battery.get("solar_power", 0)
    load_power = battery.get("load_power", 0)
    battery_pct = battery.get("battery_pct", 0)
    endurance = battery.get("endurance_hours")
    solar_forecast = battery.get("solar_forecast", 0)

    # ── Determine time context ──
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

    # ── Weather context from forecasts ──
    weather_ctx = _analyze_weather(forecasts)

    # ── Sensor context ──
    sensor_ctx = _analyze_sensors(sensors, weather_ctx, is_daytime)

    # ── Build analysis based on status ──
    if status == "emergency":
        return _build_emergency(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx
        )
    elif status == "hoarding":
        return _build_hoarding(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx
        )
    else:
        return _build_normal(
            net_power, solar_power, load_power, battery_pct,
            endurance, solar_forecast, time_ctx, is_daytime,
            weather_ctx, sensor_ctx
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


def _build_normal(net_power, solar_power, load_power, battery_pct,
                  endurance, solar_forecast, time_ctx, is_daytime,
                  weather_ctx, sensor_ctx) -> str:
    """Build analysis text for NORMAL status."""
    lines = ["🟢 Sistem Normal:"]

    # Power status
    if net_power > 0:
        lines.append(f"• Net daya +{net_power}W — solar charging aktif, surplus energi")
    elif net_power == 0:
        lines.append(f"• Daya seimbang — konsumsi {load_power}W sama dengan produksi solar")
    else:
        lines.append(f"• Net daya {net_power}W — baterai menyuplai {abs(net_power):.1f}W tambahan")

    # Battery status
    if battery_pct >= 90:
        lines.append(f"• Baterai penuh {battery_pct}% — kapasitas cadangan maksimal")
    elif battery_pct >= 70:
        lines.append(f"• Baterai {battery_pct}% — level baik, sistem stabil")
    else:
        lines.append(f"• Baterai {battery_pct}% — masih aman, charging {'aktif' if is_daytime else 'dimulai pagi'}")

    # Endurance
    if endurance and endurance < 99999:
        lines.append(f"• Estimasi daya tahan baterai: {_format_endurance(endurance)}")

    # Weather
    lines.append(f"• Cuaca: {weather_ctx['summary']}")

    # Solar context
    if is_daytime:
        if solar_power > 0:
            efficiency = (solar_power / 50) * 100  # 50W max panel
            lines.append(f"• Solar panel aktif {solar_power}W ({efficiency:.0f}% kapasitas), prediksi rata-rata {solar_forecast}W")
    else:
        lines.append(f"• Waktu {time_ctx} — solar standby, prediksi besok {solar_forecast}W rata-rata")

    # Sensor alerts (if any)
    if sensor_ctx["alerts"]:
        lines.append(f"• ⚠️ {sensor_ctx['alerts'][0]}")

    # Actuator recommendation
    lines.append("• Semua aktuator (pompa, aerator, mist) dapat beroperasi penuh")

    return "\n".join(lines)


def _build_hoarding(net_power, solar_power, load_power, battery_pct,
                    endurance, solar_forecast, time_ctx, is_daytime,
                    weather_ctx, sensor_ctx) -> str:
    """Build analysis text for HOARDING (energy saving) status."""
    lines = ["🟡 Mode Hemat Energi:"]

    # Why hoarding?
    if weather_ctx["has_rain"]:
        lines.append(f"• {weather_ctx['summary']} — output solar akan menurun")
    elif endurance and endurance < 48:
        lines.append(f"• Daya tahan baterai terbatas ({_format_endurance(endurance)}) — perlu hemat energi")
    else:
        lines.append(f"• Kondisi cuaca kurang mendukung — aktivasi mode konservasi energi")

    # Battery + power
    lines.append(f"• Baterai {battery_pct}%, net daya {net_power:+.1f}W (solar {solar_power}W, beban {load_power}W)")

    # Recommendations
    lines.append("• Rekomendasi: kurangi frekuensi pompa sirkulasi, prioritaskan aerator")

    if not is_daytime:
        lines.append(f"• Waktu {time_ctx} — matikan beban non-esensial hingga pagi")
    else:
        lines.append(f"• Prediksi solar {solar_forecast}W — di bawah optimal karena tutupan awan {weather_ctx['avg_cloud']:.0f}%")

    # Sensor alerts (prioritize)
    for alert in sensor_ctx["alerts"][:2]:
        lines.append(f"• ⚠️ {alert}")

    if endurance and endurance < 99999:
        lines.append(f"• Estimasi daya tahan: {_format_endurance(endurance)} (tanpa intervensi)")

    return "\n".join(lines)


def _build_emergency(net_power, solar_power, load_power, battery_pct,
                     endurance, solar_forecast, time_ctx, is_daytime,
                     weather_ctx, sensor_ctx) -> str:
    """Build analysis text for EMERGENCY status."""
    lines = ["🔴 Mode Darurat Aktif:"]

    # Critical reason
    if weather_ctx["has_storm"]:
        lines.append(f"• PERINGATAN: {weather_ctx['summary']}")
    elif battery_pct < 20:
        lines.append(f"• KRITIS: Baterai hanya {battery_pct}% — risiko mati total")
    else:
        lines.append(f"• Kondisi energi kritis — sumber daya tidak mencukupi kebutuhan")

    # Battery + power
    lines.append(f"• Baterai {battery_pct}%, net daya {net_power:+.1f}W (defisit {abs(net_power):.1f}W)")

    if endurance and endurance < 99999:
        lines.append(f"• ⏱️ Estimasi baterai habis dalam {_format_endurance(endurance)}")

    # Emergency actions
    lines.append("• AKSI: Matikan pompa sirkulasi & mist spray — hanya aerator yang aktif")
    lines.append("• AKSI: Aktifkan mode survival — beban minimum hingga solar recovery")

    # Solar forecast
    if is_daytime:
        lines.append(f"• Solar hanya {solar_power}W (prediksi {solar_forecast}W) — cuaca {weather_ctx['dominant_weather'].lower()}")
    else:
        lines.append(f"• Solar standby (malam) — recovery dimulai pagi jika cuaca mendukung")

    # Sensor alerts
    for alert in sensor_ctx["alerts"][:1]:
        lines.append(f"• ⚠️ {alert}")

    return "\n".join(lines)


def _format_endurance(hours: float) -> str:
    """Format endurance hours into human-readable string."""
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
        return f"{hours:.0f} jam"
    else:
        minutes = hours * 60
        return f"{minutes:.0f} menit"
