import sys, glob, random, collections, numpy as np, time
sys.path.insert(0, sys.argv[1]); import faces as F
lfw, staff_dir = sys.argv[2], sys.argv[3]
m = F.FaceModel(); rng = random.Random(0)
people = collections.defaultdict(list)
for f in glob.glob(lfw + "/*/*.jpg"): people[f.split("/")[-2]].append(f)
rich = sorted(k for k, v in people.items() if len(v) >= 10)
others = sorted(k for k, v in people.items() if len(v) < 10); rng.shuffle(others); others = others[:2500]
def emb(f):
    r = m.analyze(F.read_image(f))
    return r[0]["embedding"] if r else None
t0 = time.time(); E = {}
for k in rich + others:
    files = sorted(people[k]) if k in rich else [sorted(people[k])[0]]
    E[k] = [e for e in (emb(f) for f in files) if e is not None]
print(f"embedding LFW: {sum(len(v) for v in E.values())} wajah, {len(rich)} identitas kaya + {len(others)} orang lain, {time.time()-t0:.0f}s")
def match(q, gallery):
    best = max(((name, float(np.max(G @ q))) for name, G in gallery.items()), key=lambda x: x[1]); return best
TH = [0.30, 0.363, 0.40, 0.45, 0.50]
stat = {t: collections.Counter() for t in TH}
for trial in range(200):
    a, b = rng.sample(rich, 2); gal = {}; queries = []
    for k in (a, b):
        v = E[k][:]; rng.shuffle(v); gal[k] = np.array(v[:8]); queries += [(k, q) for q in v[8:]]
    imp = rng.sample(others, 200) + [x for x in rng.sample(rich, 20) if x not in (a, b)]
    for t in TH:
        for k, q in queries:
            n, s = match(q, gal); stat[t]["pos"] += 1; stat[t]["tp"] += (s >= t and n == k); stat[t]["wrong"] += (s >= t and n != k)
        for k in imp:
            for q in E[k][:1]:
                n, s = match(q, gal); stat[t]["neg"] += 1; stat[t]["fa"] += s >= t
print("\nSimulasi LFW (200 percobaan, 2 staf x 8 foto galeri):")
for t in TH:
    c = stat[t]; print(f"  ambang {t:.3f}: staf dikenali benar {c['tp']/c['pos']:.1%} | staf tertukar {c['wrong']/c['pos']:.2%} | orang lain lolos sebagai staf {c['fa']/c['neg']:.3%}")
d = np.load(sys.argv[4], allow_pickle=True).item(); names, SE = d["names"], d["emb"]
imp_all = [E[k][0] for k in others if E[k]]
print(f"\nStaf asli (leave-one-out, {len(SE)} foto) vs {len(imp_all)} wajah orang lain:")
for t in TH:
    ok = wrong = 0
    for i in range(len(SE)):
        gal = {n: SE[[j for j in range(len(SE)) if j != i and names[j] == n]] for n in set(names)}
        n, s = match(SE[i], gal); ok += (s >= t and n == names[i]); wrong += (s >= t and n != names[i])
    gal = {n: SE[names == n] for n in set(names)}
    fa = sum(match(q, gal)[1] >= t for q in imp_all)
    print(f"  ambang {t:.3f}: dikenali benar {ok}/{len(SE)} | tertukar {wrong} | orang lain lolos sebagai staf {fa}/{len(imp_all)} ({fa/len(imp_all):.2%})")
