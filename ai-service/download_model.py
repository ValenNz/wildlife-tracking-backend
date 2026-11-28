from __future__ import annotations

import hashlib
import os
import sys
import urllib.request
from pathlib import Path

BASE = Path(__file__).resolve().parent
MODEL_DIR = Path(os.getenv("AI_MODEL_DIR", BASE / "models"))
os.environ.setdefault("KAGGLEHUB_CACHE", str(MODEL_DIR / "kaggle"))
os.environ.setdefault("HF_HOME", str(MODEL_DIR / "huggingface"))
os.environ.setdefault("TORCH_HOME", str(MODEL_DIR / "torch"))

AUDIO_FILES = {
    "yamnet.h5": "https://storage.googleapis.com/audioset/yamnet.h5",
    "yamnet_class_map.csv": "https://raw.githubusercontent.com/tensorflow/models/master/research/audioset/yamnet/yamnet_class_map.csv",
}


def audio() -> None:
    MODEL_DIR.mkdir(exist_ok=True)
    for name, url in AUDIO_FILES.items():
        dest = MODEL_DIR / name
        if dest.exists() and dest.stat().st_size > 0:
            print(f"[lewati] {name} sudah ada")
            continue
        print(f"[unduh] {name}")
        tmp = dest.with_suffix(dest.suffix + ".part")
        urllib.request.urlretrieve(url, tmp)
        tmp.replace(dest)
        print(f"        {dest.stat().st_size / 1e6:.1f} MB, sha256 {hashlib.sha256(dest.read_bytes()).hexdigest()[:16]}...")


def vision() -> None:
    print("[unduh] SpeciesNet + MegaDetector (sekitar 500 MB, pertama kali saja)")
    sys.path.insert(0, str(BASE))
    from vision import VisionModel

    VisionModel()
    print("        SpeciesNet siap")


def reid() -> None:
    print("[unduh] MegaDescriptor-T-224 (sekitar 110 MB)")
    sys.path.insert(0, str(BASE))
    from reid import ReidModel

    ReidModel()
    print("        MegaDescriptor siap")


FACE_FILES = {
    "face_detection_yunet_2023mar.onnx": ("https://huggingface.co/opencv/face_detection_yunet/resolve/main/face_detection_yunet_2023mar.onnx",
                                          "8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4"),
    "face_recognition_sface_2021dec.onnx": ("https://huggingface.co/opencv/face_recognition_sface/resolve/main/face_recognition_sface_2021dec.onnx",
                                            "0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79"),
}


def faces() -> None:
    target = MODEL_DIR / "faces"
    target.mkdir(parents=True, exist_ok=True)
    for name, (url, sha) in FACE_FILES.items():
        dest = target / name
        if dest.exists() and hashlib.sha256(dest.read_bytes()).hexdigest() == sha:
            print(f"[lewati] {name} sudah ada")
            continue
        print(f"[unduh] {name}")
        tmp = dest.with_suffix(".part")
        urllib.request.urlretrieve(url, tmp)
        if hashlib.sha256(tmp.read_bytes()).hexdigest() != sha:
            tmp.unlink()
            raise RuntimeError(f"Checksum {name} tidak cocok; unduhan dibuang.")
        tmp.replace(dest)
    sys.path.insert(0, str(BASE))
    from faces import FaceModel

    FaceModel()
    print("        YuNet + SFace siap")


def main(argv: list[str]) -> int:
    targets = argv or ["audio", "vision", "reid", "faces"]
    failed = {}
    for t in targets:
        try:
            {"audio": audio, "vision": vision, "reid": reid, "faces": faces}[t]()
        except Exception as e:
            failed[t] = f"{type(e).__name__}: {e}"
            print(f"[GAGAL] {t}: {failed[t]}")

    print()
    for t in targets:
        print(f"  {t:7s} {'GAGAL - ' + failed[t] if t in failed else 'OK'}")
    if failed:
        print("\nSebagian model gagal. Perbaiki error di atas lalu jalankan lagi (yang sudah OK tidak diunduh ulang).")
        return 1
    print("\nSelesai. Jalankan: uvicorn app:app --host 127.0.0.1 --port 8001")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
