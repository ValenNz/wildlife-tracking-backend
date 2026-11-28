import sys, re, collections, numpy as np, warnings; warnings.filterwarnings("ignore")
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import roc_auc_score
vn, esc, lund, out = np.load(sys.argv[1]), np.load(sys.argv[2]), np.load(sys.argv[3]), sys.argv[4]
C = float(sys.argv[5]) if len(sys.argv) > 5 else 0.01
parts = []
F, L = vn["files"], vn["labels"].astype(str); idx = np.array([int(m.group(1)) if (m := re.search(r"_(\d+)_\.wav$", f)) else 0 for f in F]); comb = L == "comb_test_noise"
s = np.full(len(F), "", dtype=object)
s[np.isin(L, ["vn_bz_train_gun", "vn_bz_train_noise"])] = "train"; s[np.isin(L, ["vn_bz_valid_gun", "vn_bz_valid_noise"])] = "valid"
s[comb & (idx < 400)] = "train"; s[comb & (idx >= 400) & (idx < 800)] = "valid"; s[comb & (idx >= 800)] = "test_comb"
s[np.isin(L, ["vn_test_gun", "vn_test_noise"])] = "test_vn"; s[L == "comb_test_gun"] = "test_comb"
parts.append((vn, np.char.endswith(L, "gun").astype(int), s, L))
F, L = esc["files"], esc["labels"].astype(str); fold = np.array([int(f.split("__")[-1][0]) for f in F])
parts.append((esc, np.zeros(len(F), int), np.where(fold <= 2, "train", np.where(fold == 3, "valid", "test_esc")).astype(object), L))
F, L = lund["files"], lund["labels"].astype(str); k = np.array([int(f.split("/")[-1][:5]) for f in F]) % 5
parts.append((lund, np.char.startswith(L, "lund_gun").astype(int), np.where(k <= 2, "train", np.where(k == 3, "valid", "test_lund")).astype(object), L))
X = np.vstack([np.hstack([d["emb"], d["scores"]]) for d, *_ in parts]); y = np.concatenate([p[1] for p in parts])
split = np.concatenate([p[2] for p in parts]); src = np.concatenate([p[3] for p in parts])
tr, va = split == "train", split == "valid"
print({t: (int((split == t).sum()), int(y[split == t].sum())) for t in ["train", "valid", "test_vn", "test_comb", "test_esc", "test_lund"]}, "(n, tembakan)")
sc = StandardScaler().fit(X[tr]); w = np.where(y[tr] == 1, (y[tr] == 0).sum() / y[tr].sum(), 1.0)
clf = LogisticRegression(C=C, max_iter=8000).fit(sc.transform(X[tr]), y[tr], sample_weight=w)
p = clf.predict_proba(sc.transform(X))[:, 1]
print(f"C={C} AUC valid {roc_auc_score(y[va], p[va]):.3f}")
cands = [(th, ((p[va] >= th) & (y[va] == 1)).sum() / y[va].sum(), ((p[va] >= th) & (y[va] == 0)).sum() / (y[va] == 0).sum()) for th in np.arange(0.05, 0.995, 0.005)]
th, rec, fa = max([c for c in cands if c[2] <= 0.02], key=lambda c: c[1])
print(f"ambang {th:.3f} (valid: recall {rec:.0%}, alarm palsu {fa:.1%})")
for t in ["test_vn", "test_comb", "test_esc", "test_lund"]:
    m = split == t; q = p[m] >= th; yy = y[m]
    print(f"  {t:10s} recall {(q&(yy==1)).sum()}/{yy.sum()} | alarm palsu {(q&(yy==0)).sum()}/{(yy==0).sum()} = {(q&(yy==0)).sum()/max(1,(yy==0).sum()):.1%}")
m = split == "test_lund"
for g in sorted(set(src[m & (y == 1)])):
    mm = m & (src == g); print(f"    {g:28s} {(p[mm] >= th).sum()}/{mm.sum()}")
m = np.isin(split, ["test_esc", "test_lund"]) & (y == 0); print("  alarm palsu per kelas:", collections.Counter(src[m][p[m] >= th]).most_common(6))
if out != "-":
    np.savez(out, mean=sc.mean_.astype(np.float32), scale=sc.scale_.astype(np.float32), coef=clf.coef_[0].astype(np.float32),
             intercept=np.float32(clf.intercept_[0]), threshold=np.float32(th), band_limit_hz=np.int32(4000))
