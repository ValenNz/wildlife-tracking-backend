import csv, sys, random, collections, time, numpy as np, os
sys.path.insert(0, sys.argv[1]); os.environ.setdefault("HF_HOME", sys.argv[1] + "/models/huggingface")
from PIL import Image
from reid import ReidModel
root = sys.argv[2]
rows = [r for r in csv.reader(open(f"{root}/reid_list_train.csv")) if r]
m = ReidModel(); t0 = time.time(); E = []
for i in range(0, len(rows), 32):
    E.append(m.embed([Image.open(f"{root}/train/{r[1]}") for r in rows[i:i + 32]]))
E = np.vstack(E); ids = np.array([r[0] for r in rows])
print(f"{len(ids)} foto, {len(set(ids))} harimau, {round((time.time()-t0)/len(ids)*1000)} ms/foto")
sim = E @ E.T; np.fill_diagonal(sim, -1); o = np.argsort(-sim, 1)
print(f"Leave-one-out: top-1 {np.mean(ids[o[:,0]]==ids):.1%}, top-5 {np.mean([ids[i] in ids[o[i,:5]] for i in range(len(ids))]):.1%}")
res = collections.defaultdict(list)
for seed in range(5):
    rng = random.Random(seed); by = collections.defaultdict(list)
    for i, k in enumerate(ids): by[k].append(i)
    known = [k for k, v in by.items() if len(v) >= 5]; rng.shuffle(known)
    enrolled = set(known[: int(len(known) * 0.8)]); G, Q = [], []
    for k in known:
        v = by[k][:]; rng.shuffle(v)
        if k in enrolled: G += v[:3]; Q += v[3:]
        else: Q += v
    G, Q = np.array(G), np.array(Q); S2 = E[Q] @ E[G].T; best = S2.argmax(1); bs = S2.max(1); pred = ids[G][best]; kn = np.isin(ids[Q], list(enrolled))
    for th in [0.5, 0.6, 0.7]:
        res[th].append((np.mean((pred == ids[Q])[kn] & (bs[kn] >= th)), np.mean(bs[~kn] < th), np.mean((pred != ids[Q])[kn] & (bs[kn] >= th))))
for th, v in res.items():
    a = np.array(v).mean(0)
    print(f"Galeri 3 foto/individu, ambang {th}: dikenali benar {a[0]:.1%} | salah individu {a[2]:.1%} | individu baru ditolak {a[1]:.1%}  (rata-rata 5 pembagian acak)")
