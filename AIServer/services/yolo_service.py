"""YOLO Plant Disease Detection Service."""
import cv2
import logging
import os
import tempfile
from ultralytics import YOLO
from config import YOLO_MODEL_PATH, YOLO_CONFIDENCE

logger = logging.getLogger(__name__)
_model = None


def get_model():
    global _model
    if _model is None:
        path = os.path.abspath(YOLO_MODEL_PATH)
        logger.info(f"Loading YOLO model: {path}")
        _model = YOLO(path)
    return _model





def run_detection(frame):
    """Run YOLO inference. Returns (detections_list, annotated_frame, original_frame)."""
    model = get_model()
    results = model(frame, conf=YOLO_CONFIDENCE, verbose=False)
    result = results[0]
    detections = []
    for box in result.boxes:
        cls_id = int(box.cls[0])
        cls_name = result.names[cls_id]
        confidence = float(box.conf[0])
        x1, y1, x2, y2 = box.xyxy[0].tolist()
        detections.append({
            "class": cls_name,
            "confidence": round(confidence * 100, 1),
            "bbox": {"x1": round(x1), "y1": round(y1), "x2": round(x2), "y2": round(y2)},
        })
    annotated = result.plot()
    return detections, annotated, frame


def determine_status(detections: list) -> dict:
    """Determine plant health status from detections."""
    if not detections:
        return {"status": "healthy", "status_label": "Tanaman Sehat", "status_emoji": "✅",
                "diseases": [], "message": "Tidak ada penyakit terdeteksi."}

    diseases = []
    healthy_count = 0
    for det in detections:
        cls_lower = det["class"].lower()
        if "healthy" in cls_lower or "sehat" in cls_lower:
            healthy_count += 1
        else:
            diseases.append({"name": det["class"], "accuracy": det["confidence"]})

    if not diseases:
        return {"status": "healthy", "status_label": "Tanaman Sehat", "status_emoji": "✅",
                "diseases": [], "message": f"{healthy_count} area sehat terdeteksi."}

    diseases.sort(key=lambda d: d["accuracy"], reverse=True)
    primary = diseases[0]
    severity = "critical" if primary["accuracy"] > 80 else "warning" if primary["accuracy"] > 60 else "mild"

    return {
        "status": severity,
        "status_label": f"Penyakit Terdeteksi: {primary['name']}",
        "status_emoji": "🔴" if severity == "critical" else "🟡",
        "diseases": diseases,
        "message": f"Ditemukan {len(diseases)} jenis penyakit pada tanaman.",
    }


def save_temp_image(frame) -> str:
    """Save frame to a temporary JPEG file, return path."""
    fd, path = tempfile.mkstemp(suffix=".jpg")
    os.close(fd)
    cv2.imwrite(path, frame, [cv2.IMWRITE_JPEG_QUALITY, 85])
    return path
