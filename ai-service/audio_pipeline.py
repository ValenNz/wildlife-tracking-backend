from __future__ import annotations

import numpy as np

import audio_io
import categories
from audio_heads import ENVIRONMENT_VETO, HUMAN_VOICE_VETO, POSSIBLE_GUNSHOT, AudioHeads, features

MIN_CATEGORY_CONFIDENCE = 0.3
ENGINE_LABEL = "Model terlatih (YAMNet + classifier)"


def classify(model, heads: AudioHeads, path: str, max_seconds: float = 600.0) -> dict:
    fullband = audio_io.decode(path, model.sample_rate, max_seconds)
    narrowband = audio_io.decode(path, model.sample_rate, max_seconds, band_limit_hz=heads.band_limit_hz)

    s_fb, e_fb = model.analyze(fullband)
    s_nb, e_nb = model.analyze(narrowband)
    clip = s_fb.max(axis=0)
    peaks = s_fb.argmax(axis=0) * model.frame_hop_seconds

    p_gun = heads.gunshot_probability(features(s_nb, e_nb))
    p_cat = heads.category_probabilities(features(s_fb, e_fb))
    zero_shot = categories.classify(model.class_names, clip.tolist(), peaks.tolist())

    zero_shot_gun = zero_shot["category"] == "gunshot" and p_cat.get("environment", 0.0) < ENVIRONMENT_VETO
    gunshot_vote = p_gun >= heads.gunshot_threshold or zero_shot_gun
    possible_gunshot = False
    if gunshot_vote and p_cat.get("human_voice", 0.0) >= HUMAN_VOICE_VETO and p_gun >= POSSIBLE_GUNSHOT:
        possible_gunshot = True
    if gunshot_vote and p_cat.get("human_voice", 0.0) < HUMAN_VOICE_VETO:
        category = "gunshot"
        confidence = max(p_gun, zero_shot["confidence"] if zero_shot_gun else 0.0)
        source = "gunshot_head" if p_gun >= heads.gunshot_threshold else "zero_shot"
    else:
        category = max(p_cat, key=p_cat.get)
        confidence = p_cat[category]
        source = "category_head"
        if category == "environment" and zero_shot["category"] == "tree_falling":
            category, confidence, source = "tree_falling", zero_shot["confidence"], "zero_shot"
        elif confidence < MIN_CATEGORY_CONFIDENCE:
            category, source = "unknown", "low_confidence"

    labels = zero_shot.get("category_labels", {})
    audioset_label = labels.get(category)
    if category == "gunshot":
        gun_idx = [model.class_names.index(n) for n in categories.CATEGORY_CLASSES["gunshot"] if n in model.class_names]
        frame = int(s_nb[:, gun_idx].max(axis=1).argmax())
        detected_at = round(frame * model.frame_hop_seconds, 2)
    else:
        detected_at = zero_shot.get("category_peaks", {}).get(category)

    species = categories.SPECIES_HINTS.get(labels.get("animal_call", "")) if category == "animal_call" else None
    duration = fullband.size / model.sample_rate
    result = {"category": category, "confidence": round(float(confidence), 4), "species": species,
              "detected_at_seconds": detected_at, "audioset_label": audioset_label}

    label_id = categories.LABELS_ID.get(category, category)
    if possible_gunshot:
        label_id += " / kemungkinan tembakan beruntun"
    top = np.argsort(clip)[::-1][:5]
    return {
        "label": (f"{label_id} ({audioset_label})" if audioset_label else label_id)[:100],
        "category": category,
        "confidence": result["confidence"],
        "species": species,
        "summary": categories.summary(result, duration, ENGINE_LABEL)
        + (" Pola impulsif mirip tembakan beruntun (detektor tembakan sangat yakin); perlu verifikasi." if possible_gunshot else ""),
        "possible_gunshot": possible_gunshot,
        "detected_at_seconds": detected_at,
        "duration_seconds": round(duration, 2),
        "decision_source": source,
        "gunshot_probability": round(float(p_gun), 4),
        "gunshot_threshold": round(heads.gunshot_threshold, 4),
        "category_probabilities": {k: round(v, 4) for k, v in sorted(p_cat.items(), key=lambda kv: -kv[1])},
        "zero_shot": {"category": zero_shot["category"], "confidence": zero_shot["confidence"]},
        "predictions": [{"label": model.class_names[i], "score": round(float(clip[i]), 4)} for i in top],
    }
