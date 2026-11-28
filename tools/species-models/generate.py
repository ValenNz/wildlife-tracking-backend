import json
import math
from pathlib import Path

import numpy as np
import trimesh
from trimesh.geometry import align_vectors

OUT = Path(__file__).resolve().parents[2] / "database" / "seeders" / "species-models"


def hexrgb(value):
    value = value.lstrip("#")
    return np.array([int(value[i:i + 2], 16) for i in (0, 2, 4)] + [255], dtype=np.uint8)


class Model:
    def __init__(self):
        self.parts = []
        self.hotspots = []

    def add(self, mesh, color, shade=None):
        colors = np.tile(hexrgb(color), (len(mesh.vertices), 1))
        if shade is not None:
            for index, value in enumerate(shade(mesh.vertices)):
                if value is not None:
                    colors[index] = hexrgb(value)
        mesh.visual = trimesh.visual.ColorVisuals(mesh, vertex_colors=colors)
        self.parts.append(mesh)
        return mesh

    def blob(self, center, radii, color, shade=None, subdiv=3, rotate=None):
        mesh = trimesh.creation.icosphere(subdivisions=subdiv, radius=1.0)
        mesh.apply_scale(radii)
        if rotate is not None:
            for axis, degrees in rotate:
                mesh.apply_transform(trimesh.transformations.rotation_matrix(math.radians(degrees), axis))
        mesh.apply_translation(center)
        return self.add(mesh, color, shade)

    def tube(self, start, end, r0, r1, color, shade=None, sections=18, caps=True):
        start, end = np.array(start, float), np.array(end, float)
        length = np.linalg.norm(end - start)
        mesh = trimesh.creation.cylinder(radius=1.0, height=length, sections=sections)
        t = (mesh.vertices[:, 2] + length / 2) / length
        radius = r0 + (r1 - r0) * t
        mesh.vertices[:, 0] *= radius
        mesh.vertices[:, 1] *= radius
        mesh.apply_transform(align_vectors([0, 0, 1], (end - start) / length))
        mesh.apply_translation((start + end) / 2)
        self.add(mesh, color, shade)
        if caps:
            self.blob(start, [r0] * 3, color, shade, subdiv=2)
            self.blob(end, [r1] * 3, color, shade, subdiv=2)

    def chain(self, points, radii, color, shade=None):
        for i in range(len(points) - 1):
            self.tube(points[i], points[i + 1], radii[i], radii[i + 1], color, shade)

    def cone(self, base, tip, radius, color, sections=16):
        base, tip = np.array(base, float), np.array(tip, float)
        height = np.linalg.norm(tip - base)
        mesh = trimesh.creation.cone(radius=radius, height=height, sections=sections)
        mesh.apply_transform(align_vectors([0, 0, 1], (tip - base) / height))
        mesh.apply_translation(base)
        self.add(mesh, color)

    def eyes(self, center, spacing, radius=0.018, color="#111111"):
        cx, cy, cz = center
        for side in (-1, 1):
            self.blob([cx + side * spacing, cy, cz], [radius] * 3, color, subdiv=2)

    def hotspot(self, label, description, position, normal=(0, 0, 1)):
        self.hotspots.append({"label": label, "description": description, "position": list(position), "normal": list(normal)})

    def export(self, slug):
        mesh = trimesh.util.concatenate(self.parts)
        mesh.merge_vertices()
        shift = -mesh.bounds[0][1]
        mesh.apply_translation([0, shift, 0])
        hotspots = [
            {
                **spot,
                "position": f"{spot['position'][0]:.3f}m {spot['position'][1] + shift:.3f}m {spot['position'][2]:.3f}m",
                "normal": " ".join(f"{value:.3f}m" for value in spot["normal"]),
            }
            for spot in self.hotspots
        ]
        scene = trimesh.Scene()
        scene.add_geometry(mesh, node_name=slug, geom_name=slug)
        OUT.mkdir(parents=True, exist_ok=True)
        (OUT / f"{slug}.glb").write_bytes(trimesh.exchange.gltf.export_glb(scene))
        (OUT / f"{slug}.hotspots.json").write_text(json.dumps(hotspots, ensure_ascii=False, indent=1))
        print(f"{slug}: {len(mesh.faces)} faces, {(OUT / f'{slug}.glb').stat().st_size // 1024} KB, tinggi {mesh.bounds[1][1]:.2f} m")


def legs(m, xs, zs, top, bottom, r0, r1, color, foot=None, knee_bend=0.0):
    for x in xs:
        for z in zs:
            knee = [x, (top + bottom) / 2, z + knee_bend]
            m.tube([x, top, z], knee, r0, (r0 + r1) / 2, color)
            m.tube(knee, [x, bottom + r1, z], (r0 + r1) / 2, r1, foot or color)


def orangutan():
    m = Model()
    fur, dark, face = "#b4521e", "#8a3c14", "#4a3328"
    shaggy = lambda v: ["#c9662a" if (math.sin(v[i, 0] * 60) + math.sin(v[i, 1] * 45)) > 1.2 else None for i in range(len(v))]
    m.blob([0, 0.62, 0], [0.30, 0.36, 0.25], fur, shaggy)
    m.blob([0, 0.42, 0.02], [0.27, 0.20, 0.23], fur, shaggy)
    m.blob([0, 1.02, 0.05], [0.16, 0.17, 0.15], fur)
    m.blob([0, 1.0, 0.16], [0.11, 0.12, 0.05], face)
    m.blob([0, 0.94, 0.19], [0.07, 0.04, 0.04], "#5c4034")
    m.eyes([0, 1.04, 0.205], 0.04, 0.016)
    m.blob([0, 0.86, 0.14], [0.10, 0.08, 0.08], "#c97a3e")
    for side in (-1, 1):
        m.chain([[side * 0.30, 0.86, 0], [side * 0.48, 0.52, 0.05], [side * 0.52, 0.12, 0.12]], [0.075, 0.06, 0.05], fur, shaggy)
        m.blob([side * 0.53, 0.07, 0.15], [0.06, 0.04, 0.09], dark)
        m.chain([[side * 0.15, 0.36, 0], [side * 0.22, 0.2, 0.12], [side * 0.2, 0.05, 0.05]], [0.085, 0.07, 0.055], fur)
        m.blob([side * 0.2, 0.03, 0.1], [0.06, 0.03, 0.1], dark)
    m.hotspot("Lengan panjang", "Rentang lengan dewasa bisa sekitar 2 m, hampir 1,5× tinggi badan—cocok untuk berayun di tajuk hutan.", [0.5, 0.45, 0.1], [1, 0, 0])
    m.hotspot("Rambut jingga kemerahan", "Rambut Orangutan Sumatra lebih pucat dan panjang dibanding Orangutan Kalimantan.", [-0.25, 0.75, 0.15], [-0.5, 0, 0.8])
    m.hotspot("Janggut & wajah", "Jantan dewasa punya janggut dan bantalan pipi (flensa) yang lebih datar dari kerabatnya di Kalimantan.", [0, 0.88, 0.22])
    return m


def tiger():
    m = Model()
    orange, white, black = "#d0742a", "#f3ece0", "#1a1410"

    def stripes(v):
        out = []
        for x, y, z in v:
            if y < 0.48 and abs(x) < 0.12:
                out.append(white)
            elif math.sin(z * 34 + abs(x) * 6) > 0.62 and y > 0.45:
                out.append(black)
            else:
                out.append(None)
        return out

    m.blob([0, 0.62, 0], [0.24, 0.24, 0.72], orange, stripes)
    m.blob([0, 0.66, 0.6], [0.26, 0.26, 0.3], orange, stripes)
    m.blob([0, 0.82, 0.95], [0.19, 0.18, 0.19], orange, lambda v: [white if (y < 0.78 and z > 1.0) else (black if math.sin(y * 70) > 0.85 and abs(x) > 0.1 else None) for x, y, z in v])
    m.blob([0, 0.76, 1.12], [0.10, 0.08, 0.09], white)
    m.blob([0, 0.78, 1.21], [0.03, 0.025, 0.02], "#3a2a25")
    m.eyes([0, 0.87, 1.11], 0.07, 0.02, "#b5a01e")
    for side in (-1, 1):
        m.blob([side * 0.13, 0.98, 0.9], [0.05, 0.06, 0.03], orange)
        m.blob([side * 0.13, 0.98, 0.88], [0.035, 0.04, 0.02], black)
    legs(m, [-0.14, 0.14], [0.52, -0.5], 0.55, 0.0, 0.085, 0.06, orange, foot=white)
    m.chain([[0, 0.66, -0.7], [0, 0.6, -1.05], [0, 0.42, -1.3], [0, 0.35, -1.5]], [0.05, 0.045, 0.04, 0.035], orange,
            lambda v: [black if math.sin(v[i, 2] * 40) > 0.4 else None for i in range(len(v))])
    m.hotspot("Belang rapat", "Belang Harimau Sumatra paling rapat di antara subspesies harimau, sering bercabang dua.", [0.24, 0.7, -0.1], [1, 0, 0])
    m.hotspot("Janggut & surai pipi", "Bulu di pipi & leher lebih panjang, terutama pada jantan.", [0.17, 0.76, 1.0], [0.8, 0, 0.5])
    m.hotspot("Tubuh kecil", "Subspesies harimau terkecil yang masih hidup: jantan ±100–140 kg, panjang ±2,2–2,5 m.", [0, 0.88, 0.0], [0, 1, 0])
    return m


def rhino():
    m = Model()
    skin, hair, horn = "#6e4c3c", "#5a3a2c", "#3b2f28"
    hairy = lambda v: [hair if math.sin(v[i, 0] * 50 + v[i, 2] * 40) > 0.7 else None for i in range(len(v))]
    m.blob([0, 0.78, 0], [0.42, 0.42, 0.8], skin, hairy)
    m.blob([0, 0.78, 0.82], [0.25, 0.25, 0.3], skin, hairy)
    m.blob([0, 0.72, 1.15], [0.2, 0.2, 0.28], skin)
    m.blob([0, 0.62, 1.42], [0.13, 0.12, 0.12], skin)
    m.cone([0, 0.72, 1.45], [0, 1.0, 1.5], 0.08, horn)
    m.cone([0, 0.84, 1.25], [0, 0.95, 1.27], 0.05, horn)
    m.eyes([0, 0.8, 1.27], 0.15, 0.016)
    for side in (-1, 1):
        m.blob([side * 0.12, 0.94, 1.02], [0.04, 0.07, 0.03], skin)
        m.blob([side * 0.12, 1.0, 1.02], [0.03, 0.025, 0.02], hair)
        m.tube([side * 0.4, 0.82, 0.4], [side * 0.4, 0.82, -0.3], 0.03, 0.03, "#5e4134")
    legs(m, [-0.24, 0.24], [0.55, -0.5], 0.6, 0.0, 0.14, 0.11, skin)
    m.tube([0, 0.8, -0.78], [0, 0.55, -0.92], 0.04, 0.02, skin)
    m.hotspot("Dua cula", "Satu-satunya badak Asia bercula dua; cula depan lebih panjang (±15–25 cm).", [0, 0.95, 1.5])
    m.hotspot("Berambut", "Badak paling berbulu—kerabat terdekat badak berbulu (woolly rhino) zaman es.", [0.42, 0.9, -0.1], [1, 0, 0])
    m.hotspot("Badak terkecil", "Tinggi bahu ±1,0–1,5 m, berat ±500–1.000 kg.", [0, 1.2, 0.2], [0, 1, 0])
    return m


def elephant():
    m = Model()
    grey, dark, tusk = "#807c78", "#6a6662", "#f2eadb"
    m.blob([0, 1.5, 0], [0.75, 0.72, 1.15], grey)
    m.blob([0, 1.65, 1.0], [0.48, 0.5, 0.45], grey)
    m.blob([0, 1.95, 0.95], [0.3, 0.2, 0.3], grey)
    m.chain([[0, 1.45, 1.38], [0, 1.0, 1.52], [0, 0.55, 1.5], [0, 0.25, 1.62]], [0.16, 0.12, 0.08, 0.06], grey,
            lambda v: [dark if math.sin(v[i, 1] * 60) > 0.8 else None for i in range(len(v))])
    for side in (-1, 1):
        m.blob([side * 0.5, 1.6, 0.78], [0.08, 0.42, 0.36], dark, rotate=[([0, 1, 0], side * 25)])
        m.tube([side * 0.15, 1.3, 1.35], [side * 0.22, 1.0, 1.6], 0.045, 0.02, tusk)
    m.eyes([0, 1.72, 1.36], 0.3, 0.025)
    legs(m, [-0.42, 0.42], [0.72, -0.72], 1.25, 0.0, 0.24, 0.21, grey, foot=dark)
    m.tube([0, 1.6, -1.12], [0, 0.9, -1.25], 0.04, 0.025, grey)
    m.hotspot("Telinga kecil", "Telinga gajah Asia jauh lebih kecil dari gajah Afrika; tepinya melipat ke depan.", [0.55, 1.65, 0.8], [1, 0, 0])
    m.hotspot("Belalai", "Lebih dari 40.000 otot; ujungnya punya satu 'jari' untuk memungut benda kecil.", [0, 0.6, 1.58])
    m.hotspot("Gading pendek", "Hanya sebagian jantan bergading; betina umumnya tidak, atau gadingnya sangat pendek (tush).", [0.22, 1.0, 1.62], [0.6, 0, 0.8])
    return m


def sambar():
    m = Model()
    brown, darkb, antler, light = "#6b4e32", "#4c3522", "#8a7454", "#cdb898"
    m.blob([0, 1.0, 0], [0.26, 0.28, 0.62], brown, lambda v: [light if v[i, 1] < 0.84 and abs(v[i, 0]) < 0.12 else None for i in range(len(v))])
    m.tube([0, 1.12, 0.48], [0, 1.5, 0.78], 0.14, 0.1, brown)
    m.blob([0, 1.6, 0.9], [0.11, 0.12, 0.2], brown)
    m.blob([0, 1.53, 1.08], [0.06, 0.06, 0.06], darkb)
    m.eyes([0, 1.65, 0.98], 0.09, 0.016)
    for side in (-1, 1):
        m.blob([side * 0.13, 1.72, 0.85], [0.05, 0.08, 0.025], brown, rotate=[([0, 0, 1], side * -35)])
        base = [side * 0.06, 1.72, 0.86]
        top = [side * 0.25, 2.25, 0.75]
        m.tube(base, top, 0.025, 0.012, antler)
        m.tube([side * 0.09, 1.82, 0.84], [side * 0.15, 1.95, 1.05], 0.018, 0.008, antler)
        m.tube([side * 0.2, 2.1, 0.78], [side * 0.15, 2.3, 0.9], 0.014, 0.007, antler)
    legs(m, [-0.13, 0.13], [0.45, -0.45], 0.9, 0.0, 0.06, 0.035, brown, foot=darkb)
    m.tube([0, 1.12, -0.6], [0, 0.85, -0.72], 0.06, 0.04, darkb)
    m.hotspot("Ranggah tiga cabang", "Jantan memiliki ranggah sederhana bercabang tiga yang tumbuh dan tanggal setiap tahun.", [0.25, 2.25, 0.75], [0.7, 0.7, 0])
    m.hotspot("Rusa terbesar di Asia Tenggara", "Tinggi bahu ±1,0–1,6 m; jantan bisa >200 kg.", [0.26, 1.05, 0], [1, 0, 0])
    m.hotspot("Ekor berumbai", "Ekor gelap & cukup panjang diangkat sebagai tanda waspada.", [0, 0.9, -0.72], [0, 0, -1])
    return m


def siamang():
    m = Model()
    black, sac, branch = "#141414", "#b9a39a", "#5b4632"
    m.tube([-0.9, 2.0, 0], [0.9, 2.05, 0], 0.05, 0.045, branch, caps=False)
    m.blob([0, 1.15, 0], [0.16, 0.24, 0.13], black)
    m.blob([0, 1.48, 0.02], [0.11, 0.11, 0.1], black)
    m.blob([0, 1.47, 0.09], [0.07, 0.07, 0.03], "#2a2624")
    m.eyes([0, 1.5, 0.115], 0.03, 0.013, "#3d2c1c")
    m.blob([0, 1.33, 0.09], [0.1, 0.09, 0.08], sac)
    for side in (-1, 1):
        m.chain([[side * 0.14, 1.32, 0], [side * 0.3, 1.65, 0], [side * 0.38, 1.98, 0]], [0.04, 0.035, 0.03], black)
        m.blob([side * 0.38, 2.02, 0.03], [0.04, 0.04, 0.05], black)
        m.chain([[side * 0.08, 0.95, 0], [side * 0.12, 0.72, 0.08], [side * 0.1, 0.5, 0.04]], [0.045, 0.04, 0.03], black)
    m.hotspot("Kantong suara", "Kantong tenggorokan sebesar kepala mengembang saat 'bernyanyi'—suaranya terdengar hingga ±2 km.", [0, 1.33, 0.17])
    m.hotspot("Lengan berayun", "Bergerak dengan brakiasi (berayun dari dahan ke dahan); hampir tidak pernah turun ke tanah.", [0.3, 1.65, 0.04], [1, 0, 0])
    m.hotspot("Owa terbesar", "Siamang adalah owa terbesar; bulunya hitam legam pada jantan maupun betina.", [0, 1.15, 0.13])
    return m


def hornbill():
    m = Model()
    black, white, beak, casque, branch = "#121212", "#f2f2ee", "#f2c230", "#e5571b", "#5b4632"
    m.tube([-0.5, 0.05, 0.05], [0.5, 0.06, -0.05], 0.035, 0.03, branch, caps=False)
    m.blob([0, 0.42, 0], [0.16, 0.22, 0.26], black, rotate=[([1, 0, 0], -25)])
    m.blob([0, 0.7, 0.2], [0.1, 0.11, 0.11], black)
    m.eyes([0, 0.73, 0.25], 0.085, 0.015, "#c0201b")
    m.chain([[0, 0.69, 0.28], [0, 0.64, 0.45], [0, 0.56, 0.6]], [0.05, 0.035, 0.012], beak)
    m.chain([[0, 0.78, 0.25], [0, 0.82, 0.38], [0, 0.86, 0.48], [0, 0.92, 0.5]], [0.04, 0.045, 0.035, 0.015], casque)
    for side in (-1, 1):
        m.blob([side * 0.15, 0.45, -0.05], [0.04, 0.17, 0.25], black, rotate=[([1, 0, 0], -25)])
        m.tube([side * 0.05, 0.22, 0.0], [side * 0.05, 0.08, 0.04], 0.018, 0.012, "#4d4d4d")
    m.tube([0, 0.3, -0.18], [0, -0.02, -0.5], 0.08, 0.06, white,
           lambda v: ["#121212" if -0.4 < v[i, 2] < -0.3 else None for i in range(len(v))])
    m.hotspot("Balung (casque)", "Tonjolan jingga-merah melengkung ke atas seperti cula badak—asal nama 'rangkong badak'.", [0, 0.92, 0.48], [0, 1, 0.3])
    m.hotspot("Paruh kuning besar", "Dipakai memetik buah ara; rangkong penting sebagai 'petani hutan' penyebar biji.", [0.04, 0.62, 0.5], [1, 0, 0.3])
    m.hotspot("Ekor putih berpita hitam", "Ciri mudah dikenali saat terbang; kepakan sayapnya terdengar keras.", [0.08, 0.1, -0.4], [0, 0, -1])
    return m


def sunbear():
    m = Model()
    black, chest, muzzle = "#161412", "#e0a63a", "#c9a27a"

    def crescent(v):
        out = []
        for x, y, z in v:
            r = math.hypot(x / 0.15, (y - 0.74) / 0.16)
            out.append(chest if z > 0.47 and 0.55 < r < 1.0 and y < 0.74 else None)
        return out

    m.blob([0, 0.5, 0], [0.24, 0.26, 0.48], black)
    m.blob([0, 0.62, 0.36], [0.24, 0.26, 0.2], black, crescent)
    m.blob([0, 0.72, 0.62], [0.15, 0.14, 0.14], black)
    m.blob([0, 0.67, 0.76], [0.08, 0.07, 0.08], muzzle)
    m.blob([0, 0.69, 0.84], [0.03, 0.025, 0.02], "#1c1a18")
    m.eyes([0, 0.77, 0.73], 0.06, 0.013)
    for side in (-1, 1):
        m.blob([side * 0.11, 0.84, 0.58], [0.04, 0.035, 0.025], black)
        m.tube([side * 0.13, 0.38, 0.38], [side * 0.15, 0.04, 0.48], 0.07, 0.055, black)
        m.chain([[side * 0.15, 0.06, 0.48], [side * 0.15, 0.02, 0.58]], [0.05, 0.03], "#e8dcc8")
        m.tube([side * 0.13, 0.38, -0.35], [side * 0.14, 0.04, -0.33], 0.075, 0.06, black)
    m.hotspot("Bercak dada", "Bercak kuning-jingga berbentuk U atau bulan sabit; polanya unik tiap individu.", [0, 0.72, 0.5])
    m.hotspot("Cakar panjang", "Untuk memanjat dan membongkar sarang lebah/rayap; lidahnya bisa 20–25 cm untuk menjilat madu.", [0.15, 0.03, 0.6], [0.5, 0, 0.8])
    m.hotspot("Beruang terkecil", "Beruang terkecil di dunia: tinggi berdiri ±1,2–1,5 m, berat 25–65 kg.", [0, 0.8, 0.0], [0, 1, 0])
    return m


if __name__ == "__main__":
    for slug, build in [
        ("orangutan-sumatra", orangutan), ("harimau-sumatra", tiger), ("badak-sumatra", rhino), ("gajah-sumatra", elephant),
        ("rusa-sambar", sambar), ("siamang", siamang), ("rangkong-badak", hornbill), ("beruang-madu", sunbear),
    ]:
        build().export(slug)
