# =============================================================
# SmartGarden BRIN — AI Server Dockerfile (FastAPI + YOLO)
# Lightweight Python container for plant disease detection
# and rule-based energy analysis.
# =============================================================
FROM python:3.12-slim

# 1. Install system dependencies for OpenCV
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender1 \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Set working directory
WORKDIR /app

# 3. Copy requirements first (Docker layer caching)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# 4. Copy application source code
COPY config.py .
COPY main.py .
COPY scheduler.py .
COPY services/ ./services/

# 5. Copy YOLO model weights (~45MB)
COPY models/best.pt ./models/best.pt

# 6. Create non-root user for security
RUN useradd --create-home --shell /bin/bash aiserver
USER aiserver

# 7. Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD curl -f http://localhost:8001/health || exit 1

# 8. Expose port
EXPOSE 8001

# 9. Start AI Server
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "1"]
