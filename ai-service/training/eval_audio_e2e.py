import sys, glob, re, time, json, collections
sys.path.insert(0, sys.argv[1])
from yamnet_model import YamnetModel
from audio_heads import AudioHeads
import audio_pipeline
D = sys.argv[2]
ESC_MAP = {"chainsaw": "chainsaw", "hand_saw": "chainsaw", "crackling_fire": "fire", "dog": "dog_bark", "engine": "engine",
           "airplane": "vehicle", "helicopter": "vehicle", "train": "vehicle",
           "laughing": "human_voice", "coughing": "human_voice", "sneezing": "human_voice", "crying_baby": "human_voice",
           "breathing": "human_voice", "snoring": "human_voice", "clapping": "human_voice", "footsteps": "human_voice",
           "thunderstorm": "environment", "rain": "environment", "wind": "environment", "water_drops": "environment",
           "sea_waves": "environment", "pouring_water": "environment", "insects": "environment", "crickets": "environment",
           "chirping_birds": "animal_call", "frog": "animal_call", "crow": "animal_call", "rooster": "animal_call", "pig": "animal_call",
           "cow": "animal_call", "sheep": "animal_call", "hen": "animal_call", "cat": "animal_call",
           "fireworks": "decoy", "door_wood_knock": "decoy"}
items = []
for f in sorted(glob.glob(f"{D}/esc/*__5-*.wav")):
    c = f.split("/")[-1].split("__")[0]; items.append((f, ESC_MAP[c], "ESC-50 fold 5", c))
for g in ["AR10m", "AR50m", "AR150m", "AR350m", "hunting_rifle_10m", "hunting_rifle_50m", "hunting_rifle_150m", "hunting_rifle_350m", "shotgun10m", "shotgun50m", "shotgun150m", "shotgun350m", "hyenas", "lions_sub"]:
    for f in sorted(glob.glob(f"{D}/lund/{g}/*.wav")):
        if int(f.split("/")[-1][:5]) % 5 == 4:
            items.append((f, "animal_call" if g in ("hyenas", "lions_sub") else "gunshot", "Lund (Afrika Selatan)", g))
V = f"{D}/vn/clipsforzenodo"
for f in sorted(glob.glob(f"{V}/imagesvietnamunbalanced_clips/test/gunshot/*.wav")): items.append((f, "gunshot", "Hutan Vietnam (uji)", "gun"))
for f in sorted(glob.glob(f"{V}/imagesvietnamunbalanced_clips/test/noise/*.wav")): items.append((f, "environment", "Hutan Vietnam (uji)", "noise"))
for f in sorted(glob.glob(f"{V}/testdatacombined_clips/test/gunshot/*.wav")): items.append((f, "gunshot", "Rekaman malam kontinu", "gun"))
for f in sorted(glob.glob(f"{V}/testdatacombined_clips/test/noise/*.wav")):
    m = re.search(r"_(\d+)_\.wav$", f)
    if m and int(m.group(1)) >= 800: items.append((f, "environment", "Rekaman malam kontinu", "noise"))
R = sys.argv[1]
model = YamnetModel(f"{R}/models/yamnet.h5", f"{R}/models/yamnet_class_map.csv"); heads = AudioHeads()
out = []; t0 = time.time()
for i, (f, exp, src, sub) in enumerate(items):
    r = audio_pipeline.classify(model, heads, f)
    out.append({"file": f, "expected": exp, "source": src, "sub": sub, "pred": r["category"], "conf": r["confidence"]})
    if i % 500 == 0: print(i, "/", len(items), round(time.time() - t0), "s", flush=True)
json.dump(out, open(sys.argv[3], "w"))
print("selesai", len(out), "klip,", round((time.time() - t0) / len(out) * 1000), "ms/klip")
