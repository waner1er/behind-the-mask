<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Support\SeededRandom;

/** Le centre-ville en contre-jour au bas de la descente, ses fenêtres allumées et des silhouettes de palmiers. */
final readonly class DowntownLayer implements SceneLayer
{
    private const SILHOUETTE = '#24122e';
    private const WINDOWS = ['#ffd27a', '#ff8ab0', '#7fd4ff'];

    /** Le bas de la ville : sous l'horizon, pour combler l'espace quand la route plonge. */
    private const BASE = 104;

    private SeededRandom $random;

    public function __construct(int $seed)
    {
        $this->random = new SeededRandom($seed);
    }

    public function render(): string
    {
        $svg = '';
        for ($x = 40; $x < 200; $x += $w + $this->random->int(0, 2)) {
            $w = $this->random->int(8, 18);
            $svg .= $this->tower($x, $w);
        }
        foreach ([12, 30, 222, 268, 300] as $x) {
            $svg .= $this->palm($x, $this->random->int(30, 44));
        }

        return $svg . Svg::rect(0, self::BASE, Screen::WIDTH, Screen::HEIGHT - self::BASE, self::SILHOUETTE);
    }

    private function tower(int $x, int $w): string
    {
        $top = self::BASE - $this->random->int(18, 46);
        $svg = Svg::rect($x, $top, $w, self::BASE - $top, self::SILHOUETTE);
        for ($wy = $top + 3; $wy < self::BASE - 2; $wy += 3) {
            for ($wx = $x + 2; $wx < $x + $w - 1; $wx += 2) {
                if ($this->random->chance(18)) {
                    $svg .= Svg::rect($wx, $wy, 1, 1, $this->random->pick(self::WINDOWS));
                }
            }
        }

        return $svg;
    }

    /** Un palmier en ombre chinoise : un tronc fin et une touffe de palmes. */
    private function palm(int $x, int $height): string
    {
        $top = self::BASE - $height;
        $svg = Svg::rect($x, $top, 1, $height, self::SILHOUETTE);
        foreach ([[-6, 2], [-4, 0], [-2, -1], [1, -1], [3, 0], [5, 2], [-5, 3], [6, 3]] as [$dx, $dy]) {
            $svg .= Svg::rect(min($x, $x + $dx), $top + $dy, abs($dx) + 1, 1, self::SILHOUETTE);
        }

        return $svg;
    }
}
