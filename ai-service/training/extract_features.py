import glob, sys, time
import numpy as np
sys.path.insert(0, sys.argv[1])
import audio_io
from yamnet_model import YamnetModel
m = YamnetModel(f"{sys.argv[1]}/models/yamnet.h5", f"{sys.argv[1]}/models/yamnet_class_map.csv")
files, labels, S, E = [], [], [], []
t0 = time.time()
for spec in sys.argv[3:]:
    label, pattern = spec.split("=", 1)
    for f in sorted(glob.glob(pattern, recursive=True)):
        try:
            w = audio_io.decode(f, 16000, 600, band_limit_hz=int(__import__("os").getenv("BAND_LIMIT", "0")) or None)
        except Exception as e:
            print("skip", f, e); continue
        sc, em = m.analyze(w)
        files.append(f); labels.append(label)
        S.append(sc.max(0)); E.append(np.concatenate([em.mean(0), em.max(0)]))
np.savez_compressed(sys.argv[2], files=np.array(files), labels=np.array(labels), scores=np.array(S, dtype=np.float32), emb=np.array(E, dtype=np.float32))
print(len(files), "files in", round(time.time() - t0, 1), "s")
