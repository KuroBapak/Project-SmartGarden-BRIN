#!/bin/sh
# Generate password file from environment variables
# Runs once on container first start, or when FORCE_PASSWD_REGEN=true
if [ ! -f /mosquitto/config/passwd ] || [ "$FORCE_PASSWD_REGEN" = "true" ]; then
    mosquitto_passwd -c -b /mosquitto/config/passwd "$MQTT_AUTH_USERNAME" "$MQTT_AUTH_PASSWORD"
    echo "Password file generated for user: $MQTT_AUTH_USERNAME"
fi
exec mosquitto -c /mosquitto/config/mosquitto.conf
