"""InfluxDB Service — fetch solar panel telemetry."""
import logging
from influxdb_client import InfluxDBClient
from config import INFLUXDB_URL, INFLUXDB_TOKEN, INFLUXDB_ORG, INFLUXDB_BUCKET_SOLAR

logger = logging.getLogger(__name__)


def fetch_solar_data() -> dict:
    """Fetch latest solar panel data from InfluxDB."""
    data = {"pv_voltage": 0, "pv_current": 0, "pv_power": 0, "battery_voltage": 0,
            "battery_percentage": 0, "load_power": 0, "net_power": 0, "temperature": 0}
    if not INFLUXDB_TOKEN or not INFLUXDB_BUCKET_SOLAR:
        return data
    try:
        client = InfluxDBClient(url=INFLUXDB_URL, token=INFLUXDB_TOKEN, org=INFLUXDB_ORG, verify_ssl=False)
        query_api = client.query_api()
        query = f'''from(bucket: "{INFLUXDB_BUCKET_SOLAR}")
            |> range(start: -5m)
            |> filter(fn: (r) => r["_measurement"] == "solar_panel")
            |> last()'''
        tables = query_api.query(query, org=INFLUXDB_ORG)
        for table in tables:
            for record in table.records:
                field = record.get_field()
                if field in data:
                    data[field] = round(float(record.get_value()), 2)
        client.close()
    except Exception as e:
        logger.error(f"InfluxDB solar query error: {e}")
    return data
