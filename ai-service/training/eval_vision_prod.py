import csv, sys, os, time, collections
sys.path.insert(0, sys.argv[1]); os.environ.setdefault("KAGGLEHUB_CACHE", sys.argv[1] + "/models/kaggle")
from vision import VisionModel
rows = list(csv.DictReader(open(sys.argv[2])))
base = os.path.dirname(sys.argv[2])
m = VisionModel(); t0 = time.time()
OK = {"sambar": ({"sambar"}, {"Rusa", "Cervidae"}), "eurasian_wild_pig": ({"wild boar", "sus species"}, {"Sus", "Suidae"}),
      "pig_tailed_macaque": ({"northern pig-tailed macaque", "southern pig-tailed macaque"}, {"Macaca", "Cercopithecidae"}),
      "stump_tailed_macaque": ({"stump-tailed macaque"}, {"Macaca", "Cercopithecidae"}),
      "large_antlered_muntjac": ({"large-antlered muntjac"}, {"Muntiacus", "Cervidae"}),
      "red_muntjac": ({"northern red muntjac", "southern red muntjac"}, {"Muntiacus", "Cervidae"}),
      "chevrotain": ({"lesser oriental chevrotain", "greater oriental chevrotain", "chevrotain species"}, {"Tragulus", "Tragulidae"}),
      "common_palm_civet": ({"common palm civet"}, {"Paradoxurus", "Viverridae"}), "unidentified_bird": (set(), set())}
stat = collections.defaultdict(lambda: collections.Counter())
for r in rows:
    country = "LAO" if "/lao/" in r["source"] else "VNM"
    res = m.analyze(os.path.join(base, r["file"]), country)
    lab = r["label"]; s = stat[lab]; s["n"] += 1
    if lab == "empty":
        s["benar"] += res["animal_count"] == 0; continue
    s["terdeteksi"] += res["animal_count"] > 0
    top = res["top_species"] or {}
    sp, parents = OK[lab]
    if (top.get("common_name") or "") in sp: s["spesies"] += 1
    elif top.get("genus") in parents or top.get("family") in parents or top.get("scientific_name") in parents: s["umum"] += 1
    elif lab == "unidentified_bird" and (top.get("common_name") or "") not in ("", "mammal"): s["umum"] += 1
print(f"{len(rows)} foto, {round((time.time()-t0)/len(rows),2)} s/foto")
tot = collections.Counter()
for lab, s in stat.items():
    tot.update(s)
    if lab == "empty": print(f"{lab:24s} n={s['n']:2d} | foto kosong benar kosong {s['benar']}/{s['n']}"); continue
    print(f"{lab:24s} n={s['n']:2d} | satwa terdeteksi {s['terdeteksi']:2d} | spesies tepat {s['spesies']:2d} | benar di genus/famili {s['umum']:2d} | salah/kosong {s['n']-s['spesies']-s['umum']:2d}")
n_animal = tot["n"] - stat["empty"]["n"]
print(f"TOTAL foto bersatwa {n_animal}: terdeteksi {tot['terdeteksi']} ({tot['terdeteksi']/n_animal:.0%}), spesies tepat {tot['spesies']} ({tot['spesies']/n_animal:.0%}), benar minimal tingkat genus/famili {tot['spesies']+tot['umum']} ({(tot['spesies']+tot['umum'])/n_animal:.0%})")
