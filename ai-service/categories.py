from __future__ import annotations

import os

CATEGORY_CLASSES: dict[str, list[str]] = {
    "gunshot": ["Gunshot, gunfire", "Machine gun", "Artillery fire", "Explosion", "Cap gun",
                "Fireworks", "Firecracker"],
    "chainsaw": ["Chainsaw", "Sawing", "Power tool"],
    "fire": ["Fire", "Crackle"],
    "tree_falling": ["Wood", "Chop", "Crack", "Smash, crash"],
    "engine": ["Engine", "Light engine (high frequency)", "Medium engine (mid frequency)",
               "Heavy engine (low frequency)", "Engine knocking", "Engine starting", "Jackhammer",
               "Drill", "Tools", "Hammer"],
    "dog_bark": ["Dog", "Bark", "Howl", "Bow-wow", "Growling", "Whimper (dog)", "Canidae, dogs, wolves"],
    "vehicle": ["Vehicle", "Motor vehicle (road)", "Car", "Car passing by", "Truck", "Motorcycle",
                "Boat, Water vehicle", "Motorboat, speedboat", "Aircraft engine"],
    "human_voice": ["Speech", "Child speech, kid speaking", "Conversation", "Narration, monologue",
                    "Shout", "Yell", "Children shouting", "Hubbub, speech noise, speech babble"],
    "animal_call": ["Animal", "Wild animals", "Roaring cats (lions, tigers)", "Roar", "Bird",
                    "Bird vocalization, bird call, bird song", "Chirp, tweet", "Pigeon, dove",
                    "Bird flight, flapping wings", "Pig", "Frog", "Livestock, farm animals, working animals",
                    "Fowl", "Chicken, rooster", "Crow", "Owl", "Cat", "Domestic animals, pets"],
    "environment": ["Wind", "Wind noise (microphone)", "Thunderstorm", "Thunder", "Water", "Rain",
                    "Raindrop", "Rain on surface", "Stream", "Waterfall", "Insect", "Cricket"],
}

THREAT_CATEGORIES = ["gunshot", "chainsaw", "fire", "tree_falling", "engine", "dog_bark", "vehicle", "human_voice"]


def _threshold(category: str, default: float) -> float:
    return float(os.getenv(f"AI_THRESHOLD_{category.upper()}", default))


THRESHOLDS: dict[str, float] = {
    "gunshot": _threshold("gunshot", 0.25),
    "chainsaw": _threshold("chainsaw", 0.25),
    "fire": _threshold("fire", 0.35),
    "tree_falling": _threshold("tree_falling", 0.4),
    "engine": _threshold("engine", 0.3),
    "dog_bark": _threshold("dog_bark", 0.3),
    "vehicle": _threshold("vehicle", 0.3),
    "human_voice": _threshold("human_voice", 0.35),
}
MIN_CONFIDENCE = float(os.getenv("AI_MIN_CONFIDENCE", "0.1"))

LABELS_ID: dict[str, str] = {
    "gunshot": "tembakan / ledakan",
    "chainsaw": "gergaji mesin",
    "fire": "suara api / kebakaran",
    "tree_falling": "kayu patah / pohon tumbang",
    "engine": "mesin / alat berat",
    "dog_bark": "gonggongan anjing",
    "vehicle": "kendaraan",
    "human_voice": "suara manusia",
    "animal_call": "suara satwa",
    "environment": "suara alam",
    "unknown": "tidak dikenali",
}

SPECIES_HINTS: dict[str, str] = {
    "Roaring cats (lions, tigers)": "Harimau Sumatra",
}


def classify(class_names: list[str], clip_scores: list[float], peak_times: list[float]) -> dict:
    index = {name: i for i, name in enumerate(class_names)}

    best: dict[str, tuple[float, str]] = {}
    for category, names in CATEGORY_CLASSES.items():
        candidates = [(clip_scores[index[n]], n) for n in names if n in index]
        if candidates:
            best[category] = max(candidates)

    chosen = None
    for category in THREAT_CATEGORIES:
        score, _ = best.get(category, (0.0, ""))
        if score >= THRESHOLDS[category]:
            chosen = category
            break

    if chosen is None:
        rest = {c: v for c, v in best.items() if c not in THREAT_CATEGORIES}
        top = max(rest.items(), key=lambda kv: kv[1][0], default=None)
        chosen = top[0] if top and top[1][0] >= MIN_CONFIDENCE else "unknown"
        if chosen == "unknown" and "Silence" in index and clip_scores[index["Silence"]] >= 0.5:
            return {"category": "environment", "audioset_label": "Silence",
                    "confidence": round(float(clip_scores[index["Silence"]]), 4), "species": None,
                    "detected_at_seconds": None, "category_scores": {c: round(float(v[0]), 4) for c, v in best.items()}}

    score, audioset_label = best.get(chosen, (0.0, ""))
    species = SPECIES_HINTS.get(audioset_label) if chosen == "animal_call" else None
    at_seconds = round(peak_times[index[audioset_label]], 2) if audioset_label in index else None

    return {
        "category": chosen,
        "audioset_label": audioset_label or None,
        "confidence": round(float(score), 4),
        "species": species,
        "detected_at_seconds": at_seconds,
        "category_scores": {c: round(float(v[0]), 4) for c, v in best.items()},
        "category_labels": {c: v[1] for c, v in best.items()},
        "category_peaks": {c: round(peak_times[index[v[1]]], 2) for c, v in best.items() if v[1] in index},
    }


def summary(result: dict, duration: float, engine: str = "YAMNet") -> str:
    label = LABELS_ID.get(result["category"], result["category"])
    if result["category"] == "unknown":
        return f"Rekaman {duration:.0f} detik tidak menunjukkan suara yang dikenali dengan yakin. Perlu verifikasi petugas."
    where = f" sekitar detik ke-{result['detected_at_seconds']:.0f}" if result.get("detected_at_seconds") is not None else ""
    species = f" (kemungkinan {result['species']})" if result.get("species") else ""
    return (f"{engine} mendeteksi {label}{species}{where} dari rekaman {duration:.0f} detik "
            f"dengan keyakinan {result['confidence'] * 100:.0f}%. Perlu verifikasi petugas.")
