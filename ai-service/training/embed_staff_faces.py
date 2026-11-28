import sys, glob, itertools, numpy as np
sys.path.insert(0, sys.argv[1]); import faces as F
m = F.FaceModel(); data = []
for f in sorted(glob.glob(sys.argv[2] + "/face_*/*")):
    name = f.split("/")[-2]; res = m.analyze(F.read_image(f))
    print(f"{name:16s} wajah terdeteksi: {len(res)}  ukuran wajah terbesar: {res[0]['bbox'][2]:.2f}x{res[0]['bbox'][3]:.2f} conf {res[0]['confidence']}" if res else f"{name:16s} TIDAK ADA WAJAH  {f.split('/')[-1]}")
    if res: data.append((name, res[0]["embedding"]))
names = np.array([d[0] for d in data]); E = np.array([d[1] for d in data]); S = E @ E.T
same = [S[i, j] for i, j in itertools.combinations(range(len(E)), 2) if names[i] == names[j]]
diff = [S[i, j] for i, j in itertools.combinations(range(len(E)), 2) if names[i] != names[j]]
print(f"\nkemiripan orang SAMA : min {min(same):.3f}  rata-rata {np.mean(same):.3f}")
print(f"kemiripan orang BEDA : maks {max(diff):.3f}  rata-rata {np.mean(diff):.3f}")
np.save(sys.argv[3], {"names": names, "emb": E}, allow_pickle=True)
