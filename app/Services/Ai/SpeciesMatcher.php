<?php

namespace App\Services\Ai;

use App\Models\Species;
use Illuminate\Support\Collection;

class SpeciesMatcher
{
    private const ALIASES = [
        'orangutan' => ['orangutan', 'orang utan', 'pongo', 'great ape', 'ape'],
        'harimau' => ['harimau', 'tiger', 'panthera tigris', 'macan', 'tigris'],
        'gajah' => ['gajah', 'elephant', 'elephas'],
        'badak' => ['badak', 'rhino', 'rhinoceros', 'dicerorhinus'],
        'rusa' => ['rusa', 'deer', 'sambar', 'cervus', 'rusa unicolor'],
        'beruang' => ['beruang', 'bear', 'helarctos', 'sun bear'],
        'siamang' => ['siamang', 'gibbon', 'symphalangus', 'owa'],
        'kukang' => ['kukang', 'slow loris', 'nycticebus'],
        'trenggiling' => ['trenggiling', 'pangolin', 'manis javanica'],
        'tapir' => ['tapir', 'tapirus'],
        'rangkong' => ['rangkong', 'enggang', 'hornbill', 'buceros'],
        'beruk' => ['beruk', 'macaque', 'macaca', 'monyet'],
    ];

    private ?Collection $species = null;

    public function all(): Collection
    {
        return $this->species ??= Species::query()->get();
    }

    public function match(?string ...$labels): ?Species
    {
        $labels = array_values(array_filter(array_map(
            fn ($l) => $l === null ? null : mb_strtolower(trim($l)),
            $labels,
        )));

        if ($labels === []) {
            return null;
        }

        $species = $this->all();

        foreach ($labels as $label) {
            $hit = $species->first(fn (Species $s) => mb_strtolower($s->scientific_name) === $label
                || mb_strtolower($s->common_name) === $label);
            if ($hit) {
                return $hit;
            }
        }

        foreach ($labels as $label) {
            if (in_array($label, ['unknown', 'none', 'tidak diketahui', ''], true)) {
                continue;
            }
            $hit = $species->first(fn (Species $s) => str_contains(mb_strtolower($s->common_name), $label)
                || str_contains(mb_strtolower($s->scientific_name), $label)
                || str_contains($label, mb_strtolower($s->scientific_name)));
            if ($hit) {
                return $hit;
            }
        }

        foreach ($labels as $label) {
            foreach (self::ALIASES as $keyword => $aliases) {
                foreach ($aliases as $alias) {
                    if (! str_contains($label, $alias)) {
                        continue;
                    }
                    $hit = $species->first(fn (Species $s) => str_contains(mb_strtolower($s->common_name), $keyword)
                        || collect($aliases)->contains(fn ($a) => str_contains(mb_strtolower($s->scientific_name), $a)));
                    if ($hit) {
                        return $hit;
                    }
                }
            }
        }

        return null;
    }

    public function matchTaxon(?string $scientific, ?string $rank = null, ?string $genus = null): ?Species
    {
        $scientific = mb_strtolower(trim((string) $scientific));
        if ($scientific === '') {
            return null;
        }
        $species = $this->all();

        if ($rank === 'species') {
            $hit = $species->first(fn (Species $s) => ($name = mb_strtolower($s->scientific_name)) === $scientific
                || str_starts_with($name, $scientific . ' '));
            if ($hit) {
                return $hit;
            }
        }

        $genus = mb_strtolower(trim((string) ($genus ?: ($rank === 'genus' ? $scientific : explode(' ', $scientific)[0]))));
        if ($genus === '' || ! in_array($rank, ['species', 'genus'], true)) {
            return null;
        }
        $sameGenus = $species->filter(fn (Species $s) => explode(' ', mb_strtolower($s->scientific_name))[0] === $genus);

        return $sameGenus->count() === 1 ? $sameGenus->first() : null;
    }

    public function promptList(): string
    {
        return $this->all()
            ->map(fn (Species $s) => "- {$s->common_name} ({$s->scientific_name})")
            ->implode("\n");
    }
}
