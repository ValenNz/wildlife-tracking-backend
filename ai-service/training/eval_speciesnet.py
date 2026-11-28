import csv, sys, time, json, collections
from speciesnet import SpeciesNet
model_dir, manifest, out = sys.argv[1], sys.argv[2], sys.argv[3]
rows = list(csv.DictReader(open(manifest)))
t0 = time.time(); m = SpeciesNet(model_dir, components="all", geofence=True); print("load", round(time.time() - t0, 1), "s")
res = []; t0 = time.time()
for r in rows:
    country = "LAO" if "/lao/" in r["source"] else "VNM"
    p = m.predict(filepaths=[r["file"]], country=country, batch_size=1, progress_bars=False)["predictions"][0]
    dets = sorted([x for x in p.get("detections", []) if x["conf"] >= 0.2], key=lambda x: -x["conf"])
    top = dets[0].get("processed_classification", {}) if dets else {}
    res.append({"file": r["file"], "label": r["label"], "pred": top.get("class", "blank").split(";")[-1], "score": top.get("score"),
                "source": top.get("source"), "n_det": len(dets), "det_cats": sorted({x["label"] for x in dets}),
                "all": [(x["label"], round(x["conf"], 2), x.get("processed_classification", {}).get("class", "").split(";")[-1]) for x in dets]})
print("infer", round((time.time() - t0) / len(rows), 2), "s/img")
json.dump(res, open(out, "w"), indent=1)
