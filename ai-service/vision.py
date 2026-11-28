from __future__ import annotations

import os
import threading

DEFAULT_MODEL = os.getenv("AI_SPECIESNET_MODEL", "kaggle:google/speciesnet/pyTorch/v4.0.3a/1")
DEFAULT_COUNTRY = os.getenv("AI_COUNTRY", "IDN")
DETECTION_THRESHOLD = float(os.getenv("AI_DETECTION_THRESHOLD", "0.2"))

RANK_PRIORITY = {"species": 5, "genus": 4, "family": 3, "order": 2, "class": 1}


def species_rank_key(species: dict, detection_confidence: float = 1.0) -> tuple:
    return (RANK_PRIORITY.get(species.get("rank"), 0), species.get("score", 0) * detection_confidence)


NON_TAXA = {"blank", "animal", "no cv result", "human", "vehicle", "mammal", "bird"}


def parse_taxon(label: str) -> dict:
    parts = label.split(";")
    if len(parts) < 7:
        return {"common_name": label or None, "scientific_name": None, "rank": None, "genus": None, "family": None}
    _, klass, order, family, genus, species, common = parts[:7]
    if genus and species:
        rank, scientific = "species", f"{genus.capitalize()} {species}"
    elif genus:
        rank, scientific = "genus", genus.capitalize()
    elif family:
        rank, scientific = "family", family.capitalize()
    elif order:
        rank, scientific = "order", order.capitalize()
    else:
        rank, scientific = ("class", klass.capitalize()) if klass else (None, None)
    return {"common_name": common or None, "scientific_name": scientific, "rank": rank,
            "genus": genus.capitalize() or None, "family": family.capitalize() or None}


class VisionModel:
    def __init__(self, model_name: str = DEFAULT_MODEL):
        from speciesnet import SpeciesNet

        self.model_name = model_name
        self.model = SpeciesNet(model_name, components="all", geofence=True)
        self._lock = threading.Lock()

    @property
    def version(self) -> str:
        return "speciesnet-" + self.model_name.rstrip("/").split("/")[-2] if "/" in self.model_name else "speciesnet"

    def analyze(self, path: str, country: str | None = None, admin1: str | None = None) -> dict:
        country = (country or DEFAULT_COUNTRY).upper()
        with self._lock:
            raw = self.model.predict(filepaths=[path], country=country, admin1_region=admin1,
                                     batch_size=1, progress_bars=False)["predictions"][0]

        detections = []
        for d in raw.get("detections", []):
            if d["conf"] < DETECTION_THRESHOLD:
                continue
            category = "person" if d["label"] == "human" else d["label"]
            item = {"category": category, "confidence": round(float(d["conf"]), 4),
                    "bbox": [round(float(v), 4) for v in d["bbox"]]}
            pc = d.get("processed_classification") or {}
            if d["label"] == "animal" and pc.get("class"):
                taxon = parse_taxon(pc["class"])
                if (taxon["common_name"] or "").lower() not in NON_TAXA:
                    item["species"] = {**taxon, "score": round(float(pc.get("score", 0)), 4), "source": pc.get("source")}
            detections.append(item)
        detections.sort(key=lambda x: -x["confidence"])

        counts = {c: sum(1 for d in detections if d["category"] == c) for c in ("animal", "person", "vehicle")}
        with_species = [d for d in detections if "species" in d]
        top = max(with_species, key=lambda d: species_rank_key(d["species"], d["confidence"]))["species"] if with_species else None

        return {
            "detections": detections,
            "animal_count": counts["animal"],
            "person_count": counts["person"],
            "vehicle_count": counts["vehicle"],
            "top_species": top,
            "country": country,
            "failures": raw.get("failures"),
        }
