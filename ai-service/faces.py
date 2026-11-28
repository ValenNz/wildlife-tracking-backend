from __future__ import annotations

import os
import threading
from pathlib import Path

import cv2
import numpy as np

FACE_DIR = Path(os.getenv("AI_MODEL_DIR", Path(__file__).resolve().parent / "models")) / "faces"
DETECTOR_FILE = "face_detection_yunet_2023mar.onnx"
RECOGNIZER_FILE = "face_recognition_sface_2021dec.onnx"
SCORE_THRESHOLD = float(os.getenv("AI_FACE_DETECTION_THRESHOLD", "0.7"))
MIN_FACE_PX = int(os.getenv("AI_FACE_MIN_PX", "40"))
MAX_SIDE = int(os.getenv("AI_FACE_MAX_SIDE", "1280"))
MODEL_NAME = "yunet-2023mar+sface-2021dec"


class FaceModel:
    def __init__(self, directory: Path = FACE_DIR):
        self.detector = cv2.FaceDetectorYN.create(str(directory / DETECTOR_FILE), "", (320, 320), SCORE_THRESHOLD, 0.3, 5000)
        self.recognizer = cv2.FaceRecognizerSF.create(str(directory / RECOGNIZER_FILE), "")
        self._lock = threading.Lock()

    @property
    def version(self) -> str:
        return MODEL_NAME

    def analyze(self, image_bgr: np.ndarray) -> list[dict]:
        h, w = image_bgr.shape[:2]
        scale = min(1.0, MAX_SIDE / max(h, w))
        img = cv2.resize(image_bgr, (int(w * scale), int(h * scale))) if scale < 1.0 else image_bgr
        ih, iw = img.shape[:2]

        with self._lock:
            self.detector.setInputSize((iw, ih))
            _, found = self.detector.detect(img)
            faces = []
            for f in (found if found is not None else []):
                x, y, fw, fh = f[:4]
                if min(fw, fh) < MIN_FACE_PX * scale:
                    continue
                aligned = self.recognizer.alignCrop(img, f)
                feat = self.recognizer.feature(aligned).flatten().astype(np.float32)
                feat /= max(float(np.linalg.norm(feat)), 1e-9)
                faces.append({
                    "bbox": [round(float(max(0.0, x / iw)), 4), round(float(max(0.0, y / ih)), 4),
                             round(float(fw / iw), 4), round(float(fh / ih), 4)],
                    "confidence": round(float(f[14]), 4),
                    "embedding": feat,
                })
        faces.sort(key=lambda d: -d["bbox"][2] * d["bbox"][3])
        return faces


def read_image(path: str) -> np.ndarray:
    from PIL import Image, ImageOps

    with Image.open(path) as im:
        im = ImageOps.exif_transpose(im).convert("RGB")
        return cv2.cvtColor(np.asarray(im), cv2.COLOR_RGB2BGR)
