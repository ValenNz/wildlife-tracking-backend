import sys, re, csv, collections, numpy as np, warnings; warnings.filterwarnings("ignore")
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
sys.path.insert(0, sys.argv[1]); import categories
D = sys.argv[2]; OUT = sys.argv[3]
names = [r[2] for r in list(csv.reader(open(sys.argv[1] + "/models/yamnet_class_map.csv")))[1:]]
ESC_MAP = {"chainsaw": "chainsaw", "hand_saw": "chainsaw", "crackling_fire": "fire", "dog": "dog_bark", "engine": "engine",
           "airplane": "vehicle", "helicopter": "vehicle", "train": "vehicle",
           "laughing": "human_voice", "coughing": "human_voice", "sneezing": "human_voice", "crying_baby": "human_voice",
           "breathing": "human_voice", "snoring": "human_voice", "clapping": "human_voice", "footsteps": "human_voice",
           "thunderstorm": "environment", "rain": "environment", "wind": "environment", "water_drops": "environment",
           "sea_waves": "environment", "pouring_water": "environment", "insects": "environment", "crickets": "environment",
           "chirping_birds": "animal_call", "frog": "animal_call", "crow": "animal_call", "rooster": "animal_call", "pig": "animal_call",
           "cow": "animal_call", "sheep": "animal_call", "hen": "animal_call", "cat": "animal_call"}
DECOY = {"fireworks", "door_wood_knock"}
rows = []
def add(fb, nb, mapper, splitter):
    assert (fb["files"] == nb["files"]).all()
    Xf = np.hstack([fb["emb"], fb["scores"]]); Xn = np.hstack([nb["emb"], nb["scores"]])
    for i, (f, l) in enumerate(zip(fb["files"], fb["labels"].astype(str))):
        c = mapper(f, l)
        if c is None: continue
        rows.append((Xf[i], Xn[i], c, splitter(f, l), l))
esc_fb, esc_nb = np.load(f"{D}/esc_fb.npz"), np.load(f"{D}/esc_nb.npz")
add(esc_fb, esc_nb, lambda f, l: "decoy" if l in DECOY else ESC_MAP.get(l), lambda f, l: {1: "train", 2: "train", 3: "train", 4: "valid", 5: "test"}[int(f.split("__")[-1][0])])
lund_fb, lund_nb = np.load(f"{D}/lund_fb.npz"), np.load(f"{D}/lund_nb.npz")
add(lund_fb, lund_nb, lambda f, l: "gunshot" if l.startswith("lund_gun") else "animal_call",
    lambda f, l: ["train", "train", "train", "valid", "test"][int(f.split("/")[-1][:5]) % 5])
vn_fb, vn_nb = np.load(f"{D}/vn_all.npz"), np.load(f"{D}/vn_nb.npz")
def vn_split(f, l):
    m = re.search(r"_(\d+)_\.wav$", f); idx = int(m.group(1)) if m else 0
    if l == "comb_test_noise": return "train" if idx < 400 else ("valid" if idx < 800 else "test")
    return {"vn_bz_train": "train", "vn_bz_valid": "valid", "vn_test": "test", "comb_test": "test"}[l.rsplit("_", 1)[0]]
add(vn_fb, vn_nb, lambda f, l: "gunshot" if l.endswith("gun") else "environment", vn_split)
Xf = np.array([r[0] for r in rows]); Xn = np.array([r[1] for r in rows]); cat = np.array([r[2] for r in rows])
split = np.array([r[3] for r in rows]); src = np.array([r[4] for r in rows])
CLS = ["chainsaw", "fire", "engine", "vehicle", "dog_bark", "human_voice", "animal_call", "environment"]
tr = (split == "train") & np.isin(cat, CLS)
sc = StandardScaler().fit(Xf[tr])
cw = {c: tr.sum() / (len(CLS) * max(1, (cat[tr] == c).sum())) for c in CLS}
clf = LogisticRegression(C=float(sys.argv[4]) if len(sys.argv) > 4 else 0.01, max_iter=8000).fit(sc.transform(Xf[tr]), cat[tr], sample_weight=np.array([cw[c] for c in cat[tr]]))
P = clf.predict_proba(sc.transform(Xf)); pred_cls = clf.classes_[P.argmax(1)]; conf = P.max(1)
h = np.load(f"{D}/gunshot_head.npz"); z = (Xn - h["mean"]) / h["scale"]
pg = 1 / (1 + np.exp(-np.clip(z @ h["coef"] + h["intercept"], -50, 50)))
zs_gun = np.array([categories.classify(names, x[2048:].tolist(), [0.0] * 521)["category"] == "gunshot" for x in Xf])
HV = float(sys.argv[5]) if len(sys.argv) > 5 else 1.01
p_hv = P[:, list(clf.classes_).index("human_voice")]
ENV = float(sys.argv[6]) if len(sys.argv) > 6 else 1.01
p_env = P[:, list(clf.classes_).index("environment")]
final = np.where(((pg >= h["threshold"]) | (zs_gun & (p_env < ENV))) & (p_hv < HV), "gunshot", pred_cls)
for s in ["valid", "test"]:
    m = (split == s) & (cat != "decoy")
    print(f"\n== {s}: akurasi keseluruhan {np.mean(final[m] == cat[m]):.1%} (n={m.sum()})")
    for c in ["gunshot"] + CLS:
        mm = m & (cat == c)
        if mm.sum(): print(f"  {c:12s} recall {np.sum(final[mm] == c):4d}/{mm.sum():4d} = {np.mean(final[mm] == c):.0%}   salah jadi: {collections.Counter(final[mm][final[mm] != c]).most_common(3)}")
    threats = set(categories.THREAT_CATEGORIES); benign = m & np.isin(cat, ["environment", "animal_call"])
    print(f"  alarm palsu (suara alam/satwa jadi ancaman): {np.isin(final[benign], list(threats)).sum()}/{benign.sum()} = {np.isin(final[benign], list(threats)).mean():.1%}")
    md = (split == s) & (cat == "decoy"); print(f"  pengecoh (kembang api/ketukan kayu): {collections.Counter(final[md]).most_common(4)}")
np.savez(OUT, mean=sc.mean_.astype(np.float32), scale=sc.scale_.astype(np.float32), coef=clf.coef_.astype(np.float32),
         intercept=clf.intercept_.astype(np.float32), classes=np.array(clf.classes_))
