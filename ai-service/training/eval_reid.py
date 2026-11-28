import csv, sys, time, random, collections, numpy as np, torch, timm
from PIL import Image
import torchvision.transforms as T
root, model_id = sys.argv[1], sys.argv[2]
rows = [r for r in csv.reader(open(f"{root}/reid_list_train.csv")) if r]
t0 = time.time(); model = timm.create_model(f"hf-hub:{model_id}", num_classes=0, pretrained=True).eval(); print("load", round(time.time()-t0,1), "s")
size = int(model_id.split("-")[-1]) if model_id.split("-")[-1].isdigit() else 224
tf = T.Compose([T.Resize((size, size)), T.ToTensor(), T.Normalize([0.5, 0.5, 0.5], [0.5, 0.5, 0.5])])
ids, embs = [], []; t0 = time.time()
with torch.inference_mode():
    for i in range(0, len(rows), 32):
        batch = rows[i:i + 32]
        x = torch.stack([tf(Image.open(f"{root}/train/{r[1]}").convert("RGB")) for r in batch])
        e = torch.nn.functional.normalize(model(x), dim=-1); embs.append(e.numpy()); ids += [r[0] for r in batch]
E = np.vstack(embs); ids = np.array(ids); print(len(ids), "img,", len(set(ids)), "individu,", round((time.time()-t0)/len(ids)*1000), "ms/img, dim", E.shape[1])
sim = E @ E.T; np.fill_diagonal(sim, -1)
order = np.argsort(-sim, axis=1)
top1 = np.mean(ids[order[:, 0]] == ids); top5 = np.mean([ids[i] in ids[order[i, :5]] for i in range(len(ids))])
print(f"leave-one-out: top-1 {top1:.1%}, top-5 {top5:.1%}")
rng = random.Random(0); by = collections.defaultdict(list)
for i, k in enumerate(ids): by[k].append(i)
known = [k for k, v in by.items() if len(v) >= 5]; rng.shuffle(known)
enrolled = set(known[: int(len(known) * 0.8)]); gallery, query = [], []
for k in known:
    v = by[k][:]; rng.shuffle(v)
    if k in enrolled: gallery += v[:3]; query += v[3:]
    else: query += v
G = np.array(gallery); Q = np.array(query); S2 = E[Q] @ E[G].T
best = S2.argmax(1); bscore = S2.max(1); pred = ids[G][best]; is_known = np.isin(ids[Q], list(enrolled))
for th in [0.0, 0.5, 0.6, 0.7, 0.8]:
    acc_known = np.mean((pred == ids[Q])[is_known] & (bscore[is_known] >= th)); rej_unknown = np.mean(bscore[~is_known] < th)
    print(f"galeri 3 foto/individu, ambang {th:.1f}: individu terdaftar dikenali benar {acc_known:.1%} | individu baru ditolak (tidak dipaksa cocok) {rej_unknown:.1%}")
